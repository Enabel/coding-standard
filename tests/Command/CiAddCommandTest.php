<?php

declare(strict_types=1);

/*
 * This file is part of the Enabel Coding Standard.
 * Copyright (c) Enabel <https://github.com/Enabel>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enabel\CodingStandard\Tests\Command;

use Enabel\CodingStandard\Command\CiAddCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

final class CiAddCommandTest extends TestCase
{
    private string $tempDir;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->tempDir = sys_get_temp_dir() . '/ci-add-command-test-' . uniqid();
        mkdir($this->tempDir);

        $this->writeComposerJson([
            'name' => 'enabel/test-project',
            'require' => ['php' => '>=8.4'],
        ]);

        file_put_contents($this->tempDir . '/.php-cs-fixer.dist.php', '<?php return [];');
        file_put_contents($this->tempDir . '/phpstan.neon', 'parameters:');
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->tempDir);
    }

    public function testMissingProviderInNonInteractiveModeReturnFailure(): void
    {
        $tester = $this->createCommandTester();

        $tester->execute(['--output-dir' => $this->tempDir], ['interactive' => false]);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('--provider option is required', $tester->getDisplay());
    }

    public function testProviderAlreadyExistsReturnFailure(): void
    {
        file_put_contents($this->tempDir . '/.gitlab-ci.yml', 'stages: [test]');

        $tester = $this->createCommandTester();

        $tester->execute(['--provider' => 'gitlab', '--output-dir' => $this->tempDir], ['interactive' => false]);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('already exists', $tester->getDisplay());
    }

    public function testForceOverwritesExistingProvider(): void
    {
        file_put_contents($this->tempDir . '/.gitlab-ci.yml', 'stages: [test]');

        $tester = $this->createCommandTester();

        $tester->execute(
            ['--provider' => 'gitlab', '--force' => true, '--output-dir' => $this->tempDir],
            ['interactive' => false],
        );

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertFileExists($this->tempDir . '/.gitlab-ci.yml');
    }

    public function testGeneratesGitHubFiles(): void
    {
        $tester = $this->createCommandTester();

        $tester->execute(['--provider' => 'github', '--output-dir' => $this->tempDir], ['interactive' => false]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertFileExists($this->tempDir . '/.github/workflows/ci.yml');
        self::assertFileExists($this->tempDir . '/.github/ci/Dockerfile');

        $ci = (string) file_get_contents($this->tempDir . '/.github/workflows/ci.yml');
        self::assertStringContainsString('name:', $ci);

        $dockerfile = (string) file_get_contents($this->tempDir . '/.github/ci/Dockerfile');
        self::assertStringContainsString('php:8.4', $dockerfile);
    }

    public function testGeneratesGitLabFiles(): void
    {
        $tester = $this->createCommandTester();

        $tester->execute(['--provider' => 'gitlab', '--output-dir' => $this->tempDir], ['interactive' => false]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertFileExists($this->tempDir . '/.gitlab-ci.yml');
        self::assertFileExists($this->tempDir . '/.gitlab/ci/Dockerfile');

        $ci = (string) file_get_contents($this->tempDir . '/.gitlab-ci.yml');
        self::assertStringContainsString('stages:', $ci);

        $dockerfile = (string) file_get_contents($this->tempDir . '/.gitlab/ci/Dockerfile');
        self::assertStringContainsString('php:8.4', $dockerfile);
    }

    public function testInvalidProviderReturnFailure(): void
    {
        $tester = $this->createCommandTester();

        $tester->execute(['--provider' => 'jenkins', '--output-dir' => $this->tempDir], ['interactive' => false]);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('Invalid provider', $tester->getDisplay());
    }

    private function createCommandTester(): CommandTester
    {
        return new CommandTester(new CiAddCommand());
    }

    /**
     * @param array<string, mixed> $data
     */
    private function writeComposerJson(array $data): void
    {
        file_put_contents(
            $this->tempDir . '/composer.json',
            json_encode($data, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES),
        );
    }
}
