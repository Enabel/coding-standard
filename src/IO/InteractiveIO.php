<?php

declare(strict_types=1);

/*
 * This file is part of the Enabel Coding Standard.
 * Copyright (c) Enabel <https://github.com/Enabel>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enabel\CodingStandard\IO;

use Enabel\CodingStandard\Config\Configuration;
use Enabel\CodingStandard\Config\ConflictResolution;
use Enabel\CodingStandard\Detector\ProjectDetector;
use Symfony\Component\Console\Style\SymfonyStyle;

final class InteractiveIO
{
    public function __construct(
        private readonly SymfonyStyle $io,
    ) {
    }

    public function gatherConfiguration(string $outputDir, ProjectDetector $detector): Configuration
    {
        $this->io->title('Enabel Coding Standard Initializer');

        // Project settings
        $this->io->section('Project Settings');

        $detectedName = $detector->getProjectName() ?? basename((string) getcwd());

        /** @var string $projectName */
        $projectName = $this->io->ask(
            'Project name',
            $detectedName,
        );

        $detectedPhp = $detector->getPhpVersion() ?? '8.4';

        /** @var string $phpVersion */
        $phpVersion = $this->io->choice(
            'PHP version',
            ['8.3', '8.4', '8.5'],
            $detectedPhp,
        );

        $detectedSymfony = $detector->isSymfonyProject();
        $isSymfony = $this->io->confirm('Is this a Symfony project?', $detectedSymfony);
        $symfonyVersion = null;
        $databaseType = null;
        $databaseVersion = null;
        $includeDbAdmin = false;
        $includeFoundry = false;
        if ($isSymfony) {
            $detectedSymfonyVersion = $detector->getSymfonyVersion() ?? '8.0';

            /** @var string $symfonyVersion */
            $symfonyVersion = $this->io->choice(
                'Symfony version',
                ['7.4', '8.0'],
                $detectedSymfonyVersion,
            );

            $useDatabase = $this->io->confirm('Configure a database?', true);
            if ($useDatabase) {
                /** @var string $databaseType */
                $databaseType = $this->io->choice(
                    'Database type',
                    [
                        'mariadb' => 'MariaDB',
                        'mysql' => 'MySQL',
                        'postgresql' => 'PostgreSQL',
                    ],
                    'mariadb',
                );

                $versions = Configuration::DATABASE_VERSIONS[$databaseType];
                /** @var string $databaseVersion */
                $databaseVersion = $this->io->choice(
                    'Database version',
                    array_combine($versions, $versions),
                    $versions[0],
                );

                if (in_array($databaseType, ['mariadb', 'mysql'], true)) {
                    $includeDbAdmin = $this->io->confirm('Include phpMyAdmin?', true);
                }

                $includeFoundry = $this->io->confirm('Include Foundry & DAMA DoctrineTestBundle?', true);
            }
        }

        // Tools selection
        $this->io->section('Code Quality Tools');

        $includePhpCsFixer = $this->io->confirm('Include PHP-CS-Fixer?', true);
        $includePhpStan = $this->io->confirm('Include PHPStan?', true);
        $phpstanLevel = 9;
        if ($includePhpStan) {
            /** @var string $level */
            $level = $this->io->choice(
                'PHPStan analysis level',
                ['6', '7', '8', '9', 'max'],
                'max',
            );
            $phpstanLevel = 'max' === $level ? 9 : (int) $level;
        }
        $includeRector = $this->io->confirm('Include Rector?', true);
        $includePhpUnit = $this->io->confirm('Include PHPUnit configuration?', true);

        // Infrastructure
        $this->io->section('Infrastructure');

        /** @var string $ciProvider */
        $ciProvider = $this->io->choice(
            'CI provider',
            ['gitlab' => 'GitLab CI', 'github' => 'GitHub Actions', 'azure' => 'Azure DevOps', 'none' => 'None'],
            'none',
        );

        /** @var string $devEnvironment */
        $devEnvironment = $this->io->choice(
            'Development environment',
            Configuration::DEV_ENVIRONMENTS,
            'symfony-cli',
        );
        $includeMakefile = $this->io->confirm('Include Makefile?', true);

        // Paths
        $this->io->section('Paths');

        /** @var string $srcPath */
        $srcPath = $this->io->ask('Source directory', 'src');
        /** @var string $testsPath */
        $testsPath = $this->io->ask('Tests directory', 'tests');

        return new Configuration(
            projectName: $projectName,
            phpVersion: $phpVersion,
            phpstanLevel: $phpstanLevel,
            isSymfonyProject: $isSymfony,
            symfonyVersion: $symfonyVersion,
            ciProvider: $ciProvider,
            devEnvironment: $devEnvironment,
            includeMakefile: $includeMakefile,
            includePhpCsFixer: $includePhpCsFixer,
            includePhpStan: $includePhpStan,
            includeRector: $includeRector,
            includePhpUnit: $includePhpUnit,
            srcPath: $srcPath,
            testsPath: $testsPath,
            outputDir: $outputDir,
            conflictResolution: ConflictResolution::ASK,
            databaseType: $databaseType,
            databaseVersion: $databaseVersion,
            includeDbAdmin: $includeDbAdmin,
            includeFoundry: $includeFoundry,
        );
    }

    /**
     * @param list<string> $existingFiles
     */
    public function askConflictResolution(array $existingFiles): ConflictResolution
    {
        $this->io->warning(sprintf(
            'The following files already exist: %s',
            implode(', ', $existingFiles),
        ));

        /** @var string $choice */
        $choice = $this->io->choice(
            'How do you want to handle existing files?',
            [
                'skip' => 'Skip existing files',
                'replace' => 'Replace all existing files',
                'ask' => 'Ask for each file',
            ],
            'ask',
        );

        return ConflictResolution::from($choice);
    }

    public function askSingleFileConflict(string $file): bool
    {
        return $this->io->confirm(
            sprintf('File "%s" already exists. Replace it?', $file),
            false,
        );
    }

    public function success(string $message): void
    {
        $this->io->success($message);
    }

    public function info(string $message): void
    {
        $this->io->info($message);
    }

    public function warning(string $message): void
    {
        $this->io->warning($message);
    }

    public function writeln(string $message): void
    {
        $this->io->writeln($message);
    }
}
