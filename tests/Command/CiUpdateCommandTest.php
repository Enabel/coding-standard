<?php

declare(strict_types=1);

/*
 * This file is part of the Enabel Coding Standard.
 * Copyright (c) Enabel <https://github.com/Enabel>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enabel\CodingStandard\Tests\Command;

use Enabel\CodingStandard\Command\CiUpdateCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

final class CiUpdateCommandTest extends TestCase
{
    private string $tempDir;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->tempDir = sys_get_temp_dir() . '/ci-update-command-test-' . uniqid();
        mkdir($this->tempDir);

        // Simulate a PHP project with composer.json
        $this->writeComposerJson([
            'name' => 'enabel/test-project',
            'require' => [
                'php' => '>=8.4',
                'symfony/framework-bundle' => '^7.4',
            ],
        ]);

        // Simulate tool config files so they are detected
        file_put_contents($this->tempDir . '/.php-cs-fixer.dist.php', '<?php return [];');
        file_put_contents($this->tempDir . '/phpstan.neon', 'parameters:');
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->tempDir);
    }

    public function testFailsWhenNoCiDetected(): void
    {
        $tester = $this->executeCommand();

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('No existing CI configuration', $tester->getDisplay());
    }

    public function testRegeneratesGitLabWhenGitLabCiExists(): void
    {
        file_put_contents($this->tempDir . '/.gitlab-ci.yml', 'stages: [test]');

        $tester = $this->executeCommand();

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertFileExists($this->tempDir . '/.gitlab-ci.yml');
        self::assertFileExists($this->tempDir . '/.gitlab/ci/Dockerfile');
    }

    public function testRegeneratesBothWhenGitLabAndGitHubExist(): void
    {
        file_put_contents($this->tempDir . '/.gitlab-ci.yml', 'stages: [test]');
        $this->filesystem->mkdir($this->tempDir . '/.github/workflows');
        file_put_contents($this->tempDir . '/.github/workflows/ci.yml', 'name: CI');

        $tester = $this->executeCommand();

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());

        // GitLab files
        self::assertFileExists($this->tempDir . '/.gitlab-ci.yml');
        self::assertFileExists($this->tempDir . '/.gitlab/ci/Dockerfile');

        // GitHub files
        self::assertFileExists($this->tempDir . '/.github/workflows/ci.yml');
        self::assertFileExists($this->tempDir . '/.github/ci/Dockerfile');
    }

    public function testOverridePhpVersionChangesDockerfile(): void
    {
        file_put_contents($this->tempDir . '/.gitlab-ci.yml', 'stages: [test]');

        $tester = $this->executeCommand(['--php-version' => '8.5']);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());

        $dockerfile = (string) file_get_contents($this->tempDir . '/.gitlab/ci/Dockerfile');
        self::assertStringContainsString('php:8.5-cli', $dockerfile);
    }

    public function testOverrideDatabaseAddsDbToCi(): void
    {
        file_put_contents($this->tempDir . '/.gitlab-ci.yml', 'stages: [test]');

        $tester = $this->executeCommand([
            '--database' => 'mariadb',
            '--database-version' => '11.4',
        ]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());

        $dockerfile = (string) file_get_contents($this->tempDir . '/.gitlab/ci/Dockerfile');
        self::assertStringContainsString('pdo_mysql', $dockerfile);

        $ciYml = (string) file_get_contents($this->tempDir . '/.gitlab-ci.yml');
        self::assertStringContainsString('mariadb', $ciYml);
    }

    public function testDatabaseWithoutVersionFallsBackToLatestSupportedVersion(): void
    {
        file_put_contents($this->tempDir . '/.gitlab-ci.yml', 'stages: [test]');

        $tester = $this->executeCommand(['--database' => 'mariadb']);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());

        $ciYml = (string) file_get_contents($this->tempDir . '/.gitlab-ci.yml');
        self::assertStringContainsString('mariadb:11.8', $ciYml);
    }

    public function testDatabaseVersionWithoutTypeDefaultsToMariadb(): void
    {
        file_put_contents($this->tempDir . '/.gitlab-ci.yml', 'stages: [test]');

        $tester = $this->executeCommand(['--database-version' => '11.4']);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());

        $ciYml = (string) file_get_contents($this->tempDir . '/.gitlab-ci.yml');
        self::assertStringContainsString('mariadb:11.4', $ciYml);

        $dockerfile = (string) file_get_contents($this->tempDir . '/.gitlab/ci/Dockerfile');
        self::assertStringContainsString('pdo_mysql', $dockerfile);
    }

    /**
     * @param array<string, string> $input
     */
    private function executeCommand(array $input = []): CommandTester
    {
        $command = new CiUpdateCommand();
        $tester = new CommandTester($command);
        $tester->execute(
            array_merge(['--output-dir' => $this->tempDir], $input),
            ['interactive' => false],
        );

        return $tester;
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
