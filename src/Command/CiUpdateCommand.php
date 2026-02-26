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
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(
    name: 'ci:update',
    description: 'Update existing CI configuration',
)]
final class CiUpdateCommand extends Command
{
    protected function configure(): void
    {
        CiCommandHelper::addCiOptions($this);
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

        $detector = new ProjectDetector($outputDir);
        $providers = $detector->detectCiProviders();

        if ([] === $providers) {
            $io->error('No existing CI configuration detected. Use ci:add to add a new CI provider.');

            return Command::FAILURE;
        }

        $helper = new CiCommandHelper();
        $files = $helper->generateCiFiles($input, $outputDir, $detector, $providers);

        $writtenCount = 0;
        foreach ($files as $relativePath => $content) {
            $fullPath = $outputDir . '/' . $relativePath;
            $directory = \dirname($fullPath);

            if (!is_dir($directory)) {
                $filesystem->mkdir($directory);
            }

            $filesystem->dumpFile($fullPath, $content);
            $io->writeln(sprintf('  <info>Updated:</info> %s', $relativePath));
            ++$writtenCount;
        }

        $io->newLine();
        $io->success(sprintf(
            'CI configuration updated for %s. %d files written.',
            implode(', ', $providers),
            $writtenCount,
        ));

        return Command::SUCCESS;
    }
}
