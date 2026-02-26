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
use Enabel\CodingStandard\Generator\GitLabCiGenerator;
use Enabel\CodingStandard\Template\TemplateRenderer;
use PHPUnit\Framework\TestCase;

final class GitLabCiGeneratorTest extends TestCase
{
    private GitLabCiGenerator $generator;

    protected function setUp(): void
    {
        $templatesPath = dirname(__DIR__, 2) . '/templates';
        $renderer = new TemplateRenderer($templatesPath);
        $this->generator = new GitLabCiGenerator($renderer);
    }

    public function testSupportsReturnsTrueForGitlab(): void
    {
        $config = $this->createConfiguration(ciProvider: 'gitlab');

        self::assertTrue($this->generator->supports($config));
    }

    public function testSupportsReturnsFalseForGithub(): void
    {
        $config = $this->createConfiguration(ciProvider: 'github');

        self::assertFalse($this->generator->supports($config));
    }

    public function testSupportsReturnsFalseForNone(): void
    {
        $config = $this->createConfiguration(ciProvider: 'none');

        self::assertFalse($this->generator->supports($config));
    }

    public function testGenerateReturnsTwoFiles(): void
    {
        $config = $this->createConfiguration(ciProvider: 'gitlab');

        $files = $this->generator->generate($config);

        self::assertArrayHasKey('.gitlab-ci.yml', $files);
        self::assertArrayHasKey('.gitlab/ci/Dockerfile', $files);
    }

    public function testDockerfileContainsPhpVersion(): void
    {
        $config = $this->createConfiguration(ciProvider: 'gitlab', phpVersion: '8.4');

        $files = $this->generator->generate($config);

        self::assertStringContainsString('php:8.4-cli', $files['.gitlab/ci/Dockerfile']);
    }

    public function testDockerfileContainsPcov(): void
    {
        $config = $this->createConfiguration(ciProvider: 'gitlab');

        $files = $this->generator->generate($config);

        self::assertStringContainsString('pcov', $files['.gitlab/ci/Dockerfile']);
    }

    public function testDockerfileContainsComposer(): void
    {
        $config = $this->createConfiguration(ciProvider: 'gitlab');

        $files = $this->generator->generate($config);

        self::assertStringContainsString('composer', $files['.gitlab/ci/Dockerfile']);
    }

    public function testDockerfileContainsPdoMysqlForMariadb(): void
    {
        $config = $this->createConfiguration(ciProvider: 'gitlab', databaseType: 'mariadb', databaseVersion: '11.4');

        $files = $this->generator->generate($config);

        self::assertStringContainsString('pdo_mysql', $files['.gitlab/ci/Dockerfile']);
        self::assertStringNotContainsString('pdo_pgsql', $files['.gitlab/ci/Dockerfile']);
    }

    public function testDockerfileContainsPdoPgsqlForPostgresql(): void
    {
        $config = $this->createConfiguration(ciProvider: 'gitlab', databaseType: 'postgresql', databaseVersion: '17');

        $files = $this->generator->generate($config);

        self::assertStringContainsString('pdo_pgsql', $files['.gitlab/ci/Dockerfile']);
        self::assertStringContainsString('libpq-dev', $files['.gitlab/ci/Dockerfile']);
        self::assertStringNotContainsString('pdo_mysql', $files['.gitlab/ci/Dockerfile']);
    }

    public function testCiContainsBuildImageJob(): void
    {
        $config = $this->createConfiguration(ciProvider: 'gitlab');

        $files = $this->generator->generate($config);

        $ci = $files['.gitlab-ci.yml'];
        self::assertStringContainsString('build:image:', $ci);
        self::assertStringContainsString('docker:27', $ci);
        self::assertStringContainsString('docker login -u gitlab-ci-token -p $CI_JOB_TOKEN', $ci);
        self::assertStringContainsString('$CI_IMAGE', $ci);
    }

    public function testCiDoesNotContainAptGet(): void
    {
        $config = $this->createConfiguration(ciProvider: 'gitlab');

        $files = $this->generator->generate($config);

        self::assertStringNotContainsString('apt-get', $files['.gitlab-ci.yml']);
    }

    public function testCiDoesNotContainDockerPhpExtInstall(): void
    {
        $config = $this->createConfiguration(ciProvider: 'gitlab');

        $files = $this->generator->generate($config);

        self::assertStringNotContainsString('docker-php-ext-install', $files['.gitlab-ci.yml']);
    }

    public function testCiWithDatabaseKeepsDbWaitInPhpunit(): void
    {
        $config = $this->createConfiguration(ciProvider: 'gitlab', databaseType: 'mariadb', databaseVersion: '11.4');

        $files = $this->generator->generate($config);

        $ci = $files['.gitlab-ci.yml'];
        self::assertStringContainsString('Waiting for database', $ci);
        self::assertStringContainsString('PDO', $ci);
    }

    public function testCiWithoutDatabaseHasNoDbWait(): void
    {
        $config = $this->createConfiguration(ciProvider: 'gitlab');

        $files = $this->generator->generate($config);

        self::assertStringNotContainsString('Waiting for database', $files['.gitlab-ci.yml']);
    }

    public function testCiWithSymfonyHasLintJobs(): void
    {
        $config = $this->createConfiguration(ciProvider: 'gitlab', isSymfony: true);

        $files = $this->generator->generate($config);

        $ci = $files['.gitlab-ci.yml'];
        self::assertStringContainsString('lint:yaml:', $ci);
        self::assertStringContainsString('lint:twig:', $ci);
        self::assertStringContainsString('lint:container:', $ci);
        self::assertStringContainsString('lint:composer:', $ci);
    }

    public function testCiWithoutSymfonyHasNoLintJobs(): void
    {
        $config = $this->createConfiguration(ciProvider: 'gitlab', isSymfony: false);

        $files = $this->generator->generate($config);

        $ci = $files['.gitlab-ci.yml'];
        self::assertStringNotContainsString('lint:yaml:', $ci);
        self::assertStringNotContainsString('lint:twig:', $ci);
    }

    public function testCiUsesPreStage(): void
    {
        $config = $this->createConfiguration(ciProvider: 'gitlab');

        $files = $this->generator->generate($config);

        self::assertStringContainsString('.pre', $files['.gitlab-ci.yml']);
    }

    public function testCiUsesImageVariable(): void
    {
        $config = $this->createConfiguration(ciProvider: 'gitlab', phpVersion: '8.4');

        $files = $this->generator->generate($config);

        $ci = $files['.gitlab-ci.yml'];
        self::assertStringContainsString('CI_IMAGE: $CI_REGISTRY_IMAGE/ci:php8.4', $ci);
        self::assertStringContainsString('.ci-image:', $ci);
    }

    public function testGetTargetFilesReturnsBothFiles(): void
    {
        $expected = ['.gitlab-ci.yml', '.gitlab/ci/Dockerfile'];

        self::assertSame($expected, $this->generator->getTargetFiles());
    }

    private function createConfiguration(
        string $ciProvider = 'gitlab',
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
