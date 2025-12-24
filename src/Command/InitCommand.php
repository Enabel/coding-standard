<?php

declare(strict_types=1);

namespace Enabel\CodingStandard\Command;

use Enabel\CodingStandard\Config\Configuration;
use Enabel\CodingStandard\Config\ConflictResolution;
use Enabel\CodingStandard\Detector\ExistingConfigDetector;
use Enabel\CodingStandard\Generator\ComposerScriptsGenerator;
use Enabel\CodingStandard\Generator\DdevGenerator;
use Enabel\CodingStandard\Generator\GeneratorInterface;
use Enabel\CodingStandard\Generator\AzureDevOpsGenerator;
use Enabel\CodingStandard\Generator\GitHubActionsGenerator;
use Enabel\CodingStandard\Generator\GitLabCiGenerator;
use Enabel\CodingStandard\Generator\MakefileGenerator;
use Enabel\CodingStandard\Generator\PhpCsFixerGenerator;
use Enabel\CodingStandard\Generator\PhpStanGenerator;
use Enabel\CodingStandard\Generator\PhpUnitGenerator;
use Enabel\CodingStandard\Generator\RectorGenerator;
use Enabel\CodingStandard\IO\InteractiveIO;
use Enabel\CodingStandard\Template\TemplateRenderer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(
    name: 'init',
    description: 'Initialize coding standards configuration for a PHP/Symfony project',
)]
final class InitCommand extends Command
{
    protected function configure(): void
    {
        $this
            // Project settings
            ->addOption('project-name', null, InputOption::VALUE_REQUIRED, 'Project name')
            ->addOption('php-version', null, InputOption::VALUE_REQUIRED, 'PHP version (8.3, 8.4, 8.5)', '8.4')
            ->addOption('symfony', null, InputOption::VALUE_REQUIRED, 'Symfony version (7.4, 8.0) or "no"', 'no')

            // Tool selection
            ->addOption('php-cs-fixer', null, InputOption::VALUE_NEGATABLE, 'Include PHP-CS-Fixer', true)
            ->addOption('phpstan', null, InputOption::VALUE_NEGATABLE, 'Include PHPStan', true)
            ->addOption('phpstan-level', null, InputOption::VALUE_REQUIRED, 'PHPStan level (6-9 or max)', 'max')
            ->addOption('rector', null, InputOption::VALUE_NEGATABLE, 'Include Rector', true)
            ->addOption('phpunit', null, InputOption::VALUE_NEGATABLE, 'Include PHPUnit config', true)

            // Infrastructure
            ->addOption('ci', null, InputOption::VALUE_REQUIRED, 'CI provider (gitlab, github, azure, none)', 'none')
            ->addOption('ddev', null, InputOption::VALUE_NEGATABLE, 'Include DDEV configuration', true)
            ->addOption('makefile', null, InputOption::VALUE_NEGATABLE, 'Include Makefile', true)

            // Database
            ->addOption('database', null, InputOption::VALUE_REQUIRED, 'Database type (mariadb, mysql, postgresql)', null)
            ->addOption('database-version', null, InputOption::VALUE_REQUIRED, 'Database version', null)

            // Paths
            ->addOption('src-path', null, InputOption::VALUE_REQUIRED, 'Source directory path', 'src')
            ->addOption('tests-path', null, InputOption::VALUE_REQUIRED, 'Tests directory path', 'tests')

            // Conflict resolution
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Overwrite existing files without asking')
            ->addOption('skip-existing', null, InputOption::VALUE_NONE, 'Skip existing files without asking')

            // Output directory
            ->addOption('output-dir', 'o', InputOption::VALUE_REQUIRED, 'Output directory', '.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $interactiveIO = new InteractiveIO($io);
        $filesystem = new Filesystem();
        $detector = new ExistingConfigDetector();

        $outputDir = $input->getOption('output-dir');
        if (!is_string($outputDir)) {
            $outputDir = '.';
        }
        $outputDir = realpath($outputDir) ?: $outputDir;

        // Get configuration
        if ($input->isInteractive()) {
            $config = $interactiveIO->gatherConfiguration($outputDir);
        } else {
            $config = $this->buildConfigurationFromOptions($input, $outputDir);
        }

        // Determine conflict resolution
        $conflictResolution = $config->conflictResolution;
        if ($input->getOption('force')) {
            $conflictResolution = ConflictResolution::REPLACE;
        } elseif ($input->getOption('skip-existing')) {
            $conflictResolution = ConflictResolution::SKIP;
        }

        $config = new Configuration(
            projectName: $config->projectName,
            phpVersion: $config->phpVersion,
            phpstanLevel: $config->phpstanLevel,
            isSymfonyProject: $config->isSymfonyProject,
            symfonyVersion: $config->symfonyVersion,
            ciProvider: $config->ciProvider,
            includeDdev: $config->includeDdev,
            includeMakefile: $config->includeMakefile,
            includePhpCsFixer: $config->includePhpCsFixer,
            includePhpStan: $config->includePhpStan,
            includeRector: $config->includeRector,
            includePhpUnit: $config->includePhpUnit,
            srcPath: $config->srcPath,
            testsPath: $config->testsPath,
            outputDir: $config->outputDir,
            conflictResolution: $conflictResolution,
            databaseType: $config->databaseType,
            databaseVersion: $config->databaseVersion,
        );

        // Create generators
        $templateRenderer = new TemplateRenderer($this->getTemplatesPath());
        $generators = $this->createGenerators($templateRenderer);

        // Collect all files to generate
        $filesToGenerate = [];
        foreach ($generators as $generator) {
            if ($generator->supports($config)) {
                $filesToGenerate = array_merge($filesToGenerate, $generator->generate($config));
            }
        }

        if ([] === $filesToGenerate) {
            $io->warning('No files to generate. Please enable at least one tool or feature.');

            return Command::SUCCESS;
        }

        // Check for existing files
        $existingFiles = $detector->getExisting($outputDir, array_keys($filesToGenerate));

        if ([] !== $existingFiles && ConflictResolution::ASK === $conflictResolution && $input->isInteractive()) {
            $conflictResolution = $interactiveIO->askConflictResolution($existingFiles);
        }

        // Write files
        $writtenCount = 0;
        $skippedCount = 0;

        foreach ($filesToGenerate as $relativePath => $content) {
            $fullPath = $outputDir . '/' . $relativePath;
            $exists = in_array($relativePath, $existingFiles, true);

            if ($exists) {
                $shouldWrite = match ($conflictResolution) {
                    ConflictResolution::REPLACE => true,
                    ConflictResolution::SKIP => false,
                    ConflictResolution::ASK => $input->isInteractive() && $interactiveIO->askSingleFileConflict($relativePath),
                };

                if (!$shouldWrite) {
                    $interactiveIO->writeln(sprintf('  <comment>Skipped:</comment> %s', $relativePath));
                    ++$skippedCount;

                    continue;
                }
            }

            // Ensure directory exists
            $directory = dirname($fullPath);
            if (!is_dir($directory)) {
                $filesystem->mkdir($directory);
            }

            $filesystem->dumpFile($fullPath, $content);
            $interactiveIO->writeln(sprintf('  <info>Created:</info> %s', $relativePath));
            ++$writtenCount;
        }

        // Make bin file executable if created
        $binFile = $outputDir . '/bin/coding-standard';
        if (file_exists($binFile)) {
            chmod($binFile, 0755);
        }

        $io->newLine();
        $interactiveIO->success(sprintf(
            'Coding standards initialized! %d files created, %d skipped.',
            $writtenCount,
            $skippedCount,
        ));

        // Show next steps
        $this->showNextSteps($io, $config);

        return Command::SUCCESS;
    }

    private function buildConfigurationFromOptions(InputInterface $input, string $outputDir): Configuration
    {
        $projectName = $input->getOption('project-name');
        if (!is_string($projectName) || '' === $projectName) {
            $projectName = basename((string) getcwd());
        }

        $phpVersion = $input->getOption('php-version');
        if (!is_string($phpVersion)) {
            $phpVersion = '8.4';
        }

        $symfony = $input->getOption('symfony');
        $isSymfony = is_string($symfony) && 'no' !== $symfony;
        $symfonyVersion = $isSymfony ? $symfony : null;

        $phpstanLevel = $input->getOption('phpstan-level');
        $phpstanLevel = 'max' === $phpstanLevel ? 9 : (is_numeric($phpstanLevel) ? (int) $phpstanLevel : 9);

        $ciProvider = $input->getOption('ci');
        if (!is_string($ciProvider)) {
            $ciProvider = 'none';
        }

        $srcPath = $input->getOption('src-path');
        if (!is_string($srcPath)) {
            $srcPath = 'src';
        }

        $testsPath = $input->getOption('tests-path');
        if (!is_string($testsPath)) {
            $testsPath = 'tests';
        }

        $databaseType = $input->getOption('database');
        $databaseType = is_string($databaseType) && '' !== $databaseType ? $databaseType : null;

        $databaseVersion = $input->getOption('database-version');
        $databaseVersion = is_string($databaseVersion) && '' !== $databaseVersion ? $databaseVersion : null;

        return new Configuration(
            projectName: $projectName,
            phpVersion: $phpVersion,
            phpstanLevel: $phpstanLevel,
            isSymfonyProject: $isSymfony,
            symfonyVersion: $symfonyVersion,
            ciProvider: $ciProvider,
            includeDdev: (bool) $input->getOption('ddev'),
            includeMakefile: (bool) $input->getOption('makefile'),
            includePhpCsFixer: (bool) $input->getOption('php-cs-fixer'),
            includePhpStan: (bool) $input->getOption('phpstan'),
            includeRector: (bool) $input->getOption('rector'),
            includePhpUnit: (bool) $input->getOption('phpunit'),
            srcPath: $srcPath,
            testsPath: $testsPath,
            outputDir: $outputDir,
            conflictResolution: ConflictResolution::ASK,
            databaseType: $databaseType,
            databaseVersion: $databaseVersion,
        );
    }

    /**
     * @return list<GeneratorInterface>
     */
    private function createGenerators(TemplateRenderer $renderer): array
    {
        return [
            new PhpCsFixerGenerator($renderer),
            new PhpStanGenerator($renderer),
            new RectorGenerator($renderer),
            new PhpUnitGenerator($renderer),
            new DdevGenerator($renderer),
            new MakefileGenerator($renderer),
            new GitLabCiGenerator($renderer),
            new GitHubActionsGenerator($renderer),
            new AzureDevOpsGenerator($renderer),
            new ComposerScriptsGenerator($renderer),
        ];
    }

    private function getTemplatesPath(): string
    {
        return dirname(__DIR__, 2) . '/templates';
    }

    private function showNextSteps(SymfonyStyle $io, Configuration $config): void
    {
        $io->section('Next Steps');

        $steps = [];

        if ($config->hasAnyTool()) {
            $steps[] = 'Install tool dependencies:';
            if ($config->includePhpCsFixer) {
                $steps[] = '  composer install -d tools/php-cs-fixer';
            }
            if ($config->includePhpStan) {
                $steps[] = '  composer install -d tools/phpstan';
            }
            if ($config->includeRector) {
                $steps[] = '  composer install -d tools/rector';
            }
            $steps[] = '';
        }

        if ($config->hasDatabase()) {
            $steps[] = 'Configure your database connection in .env.local:';
            $steps[] = sprintf('  DATABASE_URL="%s"', $this->getExampleDatabaseUrl($config));
            $steps[] = '';
        }

        if ($config->includeDdev) {
            $steps[] = 'Start DDEV: ddev start';
        }

        if ($config->includeMakefile) {
            $steps[] = 'Run "make help" to see available commands';
        }

        foreach ($steps as $step) {
            $io->writeln('  ' . $step);
        }
    }

    private function getExampleDatabaseUrl(Configuration $config): string
    {
        return match ($config->databaseType) {
            'mariadb' => sprintf('mysql://user:password@127.0.0.1:3306/my_database?serverVersion=%s-MariaDB', $config->databaseVersion),
            'mysql' => sprintf('mysql://user:password@127.0.0.1:3306/my_database?serverVersion=%s', $config->databaseVersion),
            'postgresql' => sprintf('postgresql://user:password@127.0.0.1:5432/my_database?serverVersion=%s&charset=utf8', $config->databaseVersion),
            default => '',
        };
    }
}
