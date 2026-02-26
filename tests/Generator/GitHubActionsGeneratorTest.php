<?php

declare(strict_types=1);

/*
 * This file is part of the Enabel Coding Standard.
 * Copyright (c) Enabel <https://github.com/Enabel>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enabel\CodingStandard\Tests\Generator;

use Enabel\CodingStandard\Config\Configuration;
use Enabel\CodingStandard\Config\ConflictResolution;
use Enabel\CodingStandard\Generator\GitHubActionsGenerator;
use Enabel\CodingStandard\Template\TemplateRenderer;
use PHPUnit\Framework\TestCase;

final class GitHubActionsGeneratorTest extends TestCase
{
    private GitHubActionsGenerator $generator;

    protected function setUp(): void
    {
        $templatesPath = dirname(__DIR__, 2) . '/templates';
        $renderer = new TemplateRenderer($templatesPath);
        $this->generator = new GitHubActionsGenerator($renderer);
    }

    public function testSupportsReturnsTrueForGithub(): void
    {
        $config = $this->createConfiguration(ciProvider: 'github');

        self::assertTrue($this->generator->supports($config));
    }

    public function testSupportsReturnsFalseForGitlab(): void
    {
        $config = $this->createConfiguration(ciProvider: 'gitlab');

        self::assertFalse($this->generator->supports($config));
    }

    public function testSupportsReturnsFalseForNone(): void
    {
        $config = $this->createConfiguration(ciProvider: 'none');

        self::assertFalse($this->generator->supports($config));
    }

    public function testGenerateReturnsTwoFiles(): void
    {
        $config = $this->createConfiguration(ciProvider: 'github');

        $files = $this->generator->generate($config);

        self::assertArrayHasKey('.github/workflows/ci.yml', $files);
        self::assertArrayHasKey('.github/ci/Dockerfile', $files);
    }

    public function testDockerfileIsIdenticalToGitlab(): void
    {
        $config = $this->createConfiguration(ciProvider: 'github');

        $files = $this->generator->generate($config);

        self::assertStringContainsString('php:8.4-cli', $files['.github/ci/Dockerfile']);
        self::assertStringContainsString('pcov', $files['.github/ci/Dockerfile']);
        self::assertStringContainsString('composer', $files['.github/ci/Dockerfile']);
    }

    public function testCiContainsBuildImageJob(): void
    {
        $config = $this->createConfiguration(ciProvider: 'github');

        $files = $this->generator->generate($config);

        $ci = $files['.github/workflows/ci.yml'];
        self::assertStringContainsString('build-image:', $ci);
        self::assertStringContainsString('docker/build-push-action@v6', $ci);
    }

    public function testCiContainsPackagesWritePermission(): void
    {
        $config = $this->createConfiguration(ciProvider: 'github');

        $files = $this->generator->generate($config);

        self::assertStringContainsString('packages: write', $files['.github/workflows/ci.yml']);
    }

    public function testCiDoesNotContainSetupPhp(): void
    {
        $config = $this->createConfiguration(ciProvider: 'github');

        $files = $this->generator->generate($config);

        self::assertStringNotContainsString('shivammathur/setup-php', $files['.github/workflows/ci.yml']);
    }

    public function testCiJobsUseContainer(): void
    {
        $config = $this->createConfiguration(ciProvider: 'github');

        $files = $this->generator->generate($config);

        $ci = $files['.github/workflows/ci.yml'];
        self::assertStringContainsString('container:', $ci);
        self::assertStringContainsString('image: ${{ env.CI_IMAGE }}', $ci);
    }

    public function testCiWithDatabaseUsesDatabaseHostname(): void
    {
        $config = $this->createConfiguration(ciProvider: 'github', databaseType: 'mariadb', databaseVersion: '11.4');

        $files = $this->generator->generate($config);

        $ci = $files['.github/workflows/ci.yml'];
        self::assertStringContainsString('database', $ci);
        self::assertStringContainsString('DATABASE_URL:', $ci);
    }

    public function testCiWithDatabaseDoesNotExposePorts(): void
    {
        $config = $this->createConfiguration(ciProvider: 'github', databaseType: 'mariadb', databaseVersion: '11.4');

        $files = $this->generator->generate($config);

        self::assertStringNotContainsString('ports:', $files['.github/workflows/ci.yml']);
    }

    public function testCiWithPostgresqlDatabase(): void
    {
        $config = $this->createConfiguration(ciProvider: 'github', databaseType: 'postgresql', databaseVersion: '17');

        $files = $this->generator->generate($config);

        $ci = $files['.github/workflows/ci.yml'];
        self::assertStringContainsString('postgres:17', $ci);
        self::assertStringContainsString('pg_isready', $ci);

        $dockerfile = $files['.github/ci/Dockerfile'];
        self::assertStringContainsString('pdo_pgsql', $dockerfile);
        self::assertStringContainsString('libpq-dev', $dockerfile);
    }

    public function testCiWithSymfonyHasLintJob(): void
    {
        $config = $this->createConfiguration(ciProvider: 'github', isSymfony: true);

        $files = $this->generator->generate($config);

        $ci = $files['.github/workflows/ci.yml'];
        self::assertStringContainsString('lint:', $ci);
        self::assertStringContainsString('lint:yaml', $ci);
        self::assertStringContainsString('lint:twig', $ci);
    }

    public function testCiWithoutSymfonyHasNoLintJob(): void
    {
        $config = $this->createConfiguration(ciProvider: 'github', isSymfony: false);

        $files = $this->generator->generate($config);

        self::assertStringNotContainsString('lint:', $files['.github/workflows/ci.yml']);
    }

    public function testCiContainsGhcrImage(): void
    {
        $config = $this->createConfiguration(ciProvider: 'github', phpVersion: '8.4');

        $files = $this->generator->generate($config);

        self::assertStringContainsString('ghcr.io/${{ github.repository }}/ci:php8.4', $files['.github/workflows/ci.yml']);
    }

    public function testCiJobsNeedBuildImage(): void
    {
        $config = $this->createConfiguration(ciProvider: 'github');

        $files = $this->generator->generate($config);

        $ci = $files['.github/workflows/ci.yml'];
        self::assertStringContainsString('needs: [build-image]', $ci);
        self::assertStringContainsString('needs: [build-image, build]', $ci);
    }

    public function testGetTargetFilesReturnsBothFiles(): void
    {
        $expected = ['.github/workflows/ci.yml', '.github/ci/Dockerfile'];

        self::assertSame($expected, $this->generator->getTargetFiles());
    }

    private function createConfiguration(
        string $ciProvider = 'github',
        string $phpVersion = '8.4',
        bool $isSymfony = true,
        bool $includePhpCsFixer = true,
        bool $includePhpStan = true,
        ?string $databaseType = null,
        ?string $databaseVersion = null,
    ): Configuration {
        return new Configuration(
            projectName: 'test-project',
            phpVersion: $phpVersion,
            phpstanLevel: 9,
            isSymfonyProject: $isSymfony,
            symfonyVersion: '8.0',
            ciProvider: $ciProvider,
            devEnvironment: 'local',
            includeMakefile: false,
            includePhpCsFixer: $includePhpCsFixer,
            includePhpStan: $includePhpStan,
            includeRector: false,
            includePhpUnit: true,
            srcPath: 'src',
            testsPath: 'tests',
            outputDir: '.',
            conflictResolution: ConflictResolution::ASK,
            databaseType: $databaseType,
            databaseVersion: $databaseVersion,
        );
    }
}
