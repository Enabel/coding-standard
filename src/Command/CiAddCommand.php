<?php

declare(strict_types=1);

/*
 * This file is part of the Enabel Coding Standard.
 * Copyright (c) Enabel <https://github.com/Enabel>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enabel\CodingStandard\Command;

use Enabel\CodingStandard\Detector\ProjectDetector;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(
    name: 'ci:add',
    description: 'Add CI configuration for a new provider',
)]
final class CiAddCommand extends Command
{
    private const array VALID_PROVIDERS = ['gitlab', 'github', 'azure'];

    protected function configure(): void
    {
        CiCommandHelper::addCiOptions($this);

        $this
            ->addOption('provider', null, InputOption::VALUE_REQUIRED, 'CI provider (gitlab, github, azure)')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Overwrite if provider already exists');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $filesystem = new Filesystem();

        $outputDir = $input->getOption('output-dir');
        if (!\is_string($outputDir)) {
            $outputDir = '.';
        }
        $outputDir = realpath($outputDir) ?: $outputDir;

        $provider = $input->getOption('provider');

        if (!\is_string($provider) || '' === $provider) {
            if ($input->isInteractive()) {
                $provider = $io->choice('Which CI provider?', self::VALID_PROVIDERS);
            } else {
                $io->error('The --provider option is required in non-interactive mode.');

                return Command::FAILURE;
            }
        }

        if (!\in_array($provider, self::VALID_PROVIDERS, true)) {
            $io->error(sprintf('Invalid provider "%s". Valid providers: %s', $provider, implode(', ', self::VALID_PROVIDERS)));

            return Command::FAILURE;
        }

        $detector = new ProjectDetector($outputDir);
        $existingProviders = $detector->detectCiProviders();
        $force = (bool) $input->getOption('force');

        if (\in_array($provider, $existingProviders, true) && !$force) {
            $io->error(sprintf('CI configuration for "%s" already exists. Use --force to overwrite.', $provider));

            return Command::FAILURE;
        }

        $helper = new CiCommandHelper();
        $files = $helper->generateCiFiles($input, $outputDir, $detector, [$provider]);

        $writtenCount = 0;
        foreach ($files as $relativePath => $content) {
            $fullPath = $outputDir . '/' . $relativePath;
            $directory = \dirname($fullPath);

            if (!is_dir($directory)) {
                $filesystem->mkdir($directory);
            }

            $filesystem->dumpFile($fullPath, $content);
            $io->writeln(sprintf('  <info>Created:</info> %s', $relativePath));
            ++$writtenCount;
        }

        $io->newLine();
        $io->success(sprintf(
            'CI configuration for %s added. %d files written.',
            $provider,
            $writtenCount,
        ));

        return Command::SUCCESS;
    }
}
