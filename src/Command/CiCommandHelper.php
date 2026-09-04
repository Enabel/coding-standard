<?php

declare(strict_types=1);

/*
 * This file is part of the Enabel Coding Standard.
 * Copyright (c) Enabel <https://github.com/Enabel>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enabel\CodingStandard\Command;

use Enabel\CodingStandard\Config\Configuration;
use Enabel\CodingStandard\Config\ConflictResolution;
use Enabel\CodingStandard\Detector\ProjectDetector;
use Enabel\CodingStandard\Generator\AzureDevOpsGenerator;
use Enabel\CodingStandard\Generator\GeneratorInterface;
use Enabel\CodingStandard\Generator\GitHubActionsGenerator;
use Enabel\CodingStandard\Generator\GitLabCiGenerator;
use Enabel\CodingStandard\Template\TemplateRenderer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

final class CiCommandHelper
{
    public static function addCiOptions(Command $command): void
    {
        $command
            ->addOption('php-version', null, InputOption::VALUE_REQUIRED, 'PHP version (8.4, 8.5)')
            ->addOption('symfony', null, InputOption::VALUE_REQUIRED, 'Symfony version (7.4, 8.0) or "no"')
            ->addOption('database', null, InputOption::VALUE_REQUIRED, 'Database type (mariadb, mysql, postgresql), defaults to mariadb')
            ->addOption('database-version', null, InputOption::VALUE_REQUIRED, 'Database version (defaults to the most recent supported version)')
            ->addOption('php-cs-fixer', null, InputOption::VALUE_NEGATABLE, 'Include PHP-CS-Fixer')
            ->addOption('phpstan', null, InputOption::VALUE_NEGATABLE, 'Include PHPStan')
            ->addOption('rector', null, InputOption::VALUE_NEGATABLE, 'Include Rector')
            ->addOption('output-dir', 'o', InputOption::VALUE_REQUIRED, 'Output directory', '.');
    }

    public function buildConfiguration(InputInterface $input, string $outputDir, ProjectDetector $detector, string $ciProvider): Configuration
    {
        $projectName = $detector->getProjectName() ?? basename($outputDir);

        $phpVersion = $this->resolveOption($input, 'php-version') ?? $detector->getPhpVersion() ?? Configuration::DEFAULT_PHP_VERSION;

        $symfony = $this->resolveOption($input, 'symfony');
        if (null === $symfony) {
            $isSymfony = $detector->isSymfonyProject();
            $symfonyVersion = $isSymfony ? ($detector->getSymfonyVersion() ?? '8.0') : null;
        } else {
            $isSymfony = 'no' !== $symfony;
            $symfonyVersion = $isSymfony ? $symfony : null;
        }

        $databaseType = $this->resolveOption($input, 'database');
        $databaseVersion = $this->resolveOption($input, 'database-version');

        if (null === $databaseType) {
            $dbInfo = $detector->detectDatabase();
            if (null !== $dbInfo) {
                $databaseType = $dbInfo['type'];
                $databaseVersion ??= $dbInfo['version'];
            } elseif (null !== $databaseVersion) {
                $databaseType = Configuration::DEFAULT_DATABASE_TYPE;
            }
        }

        if (null !== $databaseType && null === $databaseVersion) {
            $databaseVersion = Configuration::getDefaultDatabaseVersion($databaseType);
        }

        $includePhpCsFixer = $this->resolveNegatableOption($input, 'php-cs-fixer') ?? $detector->hasPhpCsFixer();
        $includePhpStan = $this->resolveNegatableOption($input, 'phpstan') ?? $detector->hasPhpStan();
        $includeRector = $this->resolveNegatableOption($input, 'rector') ?? $detector->hasRector();

        return new Configuration(
            projectName: $projectName,
            phpVersion: $phpVersion,
            phpstanLevel: 9,
            isSymfonyProject: $isSymfony,
            symfonyVersion: $symfonyVersion,
            ciProvider: $ciProvider,
            devEnvironment: 'local',
            includeMakefile: false,
            includePhpCsFixer: $includePhpCsFixer,
            includePhpStan: $includePhpStan,
            includeRector: $includeRector,
            includePhpUnit: $detector->hasPhpUnit(),
            srcPath: 'src',
            testsPath: 'tests',
            outputDir: $outputDir,
            conflictResolution: ConflictResolution::REPLACE,
            databaseType: $databaseType,
            databaseVersion: $databaseVersion,
        );
    }

    /**
     * @param list<string> $providers
     *
     * @return array<string, string> Map of relative file paths to content
     */
    public function generateCiFiles(InputInterface $input, string $outputDir, ProjectDetector $detector, array $providers): array
    {
        $templateRenderer = new TemplateRenderer($this->getTemplatesPath());
        $generators = $this->createCiGenerators($templateRenderer);
        $files = [];

        foreach ($providers as $provider) {
            $config = $this->buildConfiguration($input, $outputDir, $detector, $provider);

            foreach ($generators as $generator) {
                if ($generator->supports($config)) {
                    $files = array_merge($files, $generator->generate($config));
                }
            }
        }

        return $files;
    }

    /**
     * @return list<GeneratorInterface>
     */
    private function createCiGenerators(TemplateRenderer $renderer): array
    {
        return [
            new GitLabCiGenerator($renderer),
            new GitHubActionsGenerator($renderer),
            new AzureDevOpsGenerator($renderer),
        ];
    }

    private function getTemplatesPath(): string
    {
        return dirname(__DIR__, 2) . '/templates';
    }

    private function resolveOption(InputInterface $input, string $name): ?string
    {
        if (!$input->hasParameterOption('--' . $name)) {
            return null;
        }

        $value = $input->getOption($name);

        return \is_string($value) && '' !== $value ? $value : null;
    }

    private function resolveNegatableOption(InputInterface $input, string $name): ?bool
    {
        if (!$input->hasParameterOption('--' . $name) && !$input->hasParameterOption('--no-' . $name)) {
            return null;
        }

        return (bool) $input->getOption($name);
    }
}
