<?php

declare(strict_types=1);

/*
 * This file is part of the Enabel Coding Standard.
 * Copyright (c) Enabel <https://github.com/Enabel>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enabel\CodingStandard\Tests\Command;

use Enabel\CodingStandard\Command\InitCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

final class InitCommandTest extends TestCase
{
    private string $tempDir;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->tempDir = sys_get_temp_dir() . '/init-command-test-' . uniqid();
        mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->tempDir);
    }

    public function testSymfonyProjectGetsMariadbByDefault(): void
    {
        $tester = $this->executeCommand(['--symfony' => '8.0', '--dev-env' => 'docker']);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());

        $compose = (string) file_get_contents($this->tempDir . '/compose.yaml');
        self::assertStringContainsString('mariadb:11.8', $compose);
        self::assertStringContainsString('MYSQL_DATABASE', $compose);

        $dockerfile = (string) file_get_contents($this->tempDir . '/Dockerfile');
        self::assertStringContainsString('pdo_mysql', $dockerfile);
    }

    public function testDatabaseVersionCanBeOverridden(): void
    {
        $tester = $this->executeCommand([
            '--symfony' => '8.0',
            '--dev-env' => 'docker',
            '--database-version' => '11.4',
        ]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());

        $compose = (string) file_get_contents($this->tempDir . '/compose.yaml');
        self::assertStringContainsString('mariadb:11.4', $compose);
    }

    public function testNoDatabaseOptionSkipsDatabase(): void
    {
        $tester = $this->executeCommand([
            '--symfony' => '8.0',
            '--dev-env' => 'docker',
            '--database' => false,
        ]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertFileDoesNotExist($this->tempDir . '/compose.yaml');
    }

    public function testNonSymfonyProjectHasNoDatabase(): void
    {
        $tester = $this->executeCommand(['--symfony' => 'no', '--dev-env' => 'docker']);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertFileDoesNotExist($this->tempDir . '/compose.yaml');
    }

    /**
     * @param array<string, bool|string> $input
     */
    private function executeCommand(array $input = []): CommandTester
    {
        $command = new InitCommand();
        $tester = new CommandTester($command);
        $tester->execute(
            array_merge(['--output-dir' => $this->tempDir], $input),
            ['interactive' => false],
        );

        return $tester;
    }
}
