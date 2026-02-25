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
use Enabel\CodingStandard\Generator\DockerComposeGenerator;
use Enabel\CodingStandard\Template\TemplateRenderer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DockerComposeGeneratorTest extends TestCase
{
    private DockerComposeGenerator $generator;

    protected function setUp(): void
    {
        $templatesPath = dirname(__DIR__, 2) . '/templates';
        $renderer = new TemplateRenderer($templatesPath);
        $this->generator = new DockerComposeGenerator($renderer);
    }

    public function testSupportsReturnsTrueForSymfonyCliWithDatabase(): void
    {
        $config = $this->createConfiguration(devEnvironment: 'symfony-cli', databaseType: 'mariadb', databaseVersion: '11.4');

        self::assertTrue($this->generator->supports($config));
    }

    public function testSupportsReturnsTrueForDockerWithDatabase(): void
    {
        $config = $this->createConfiguration(devEnvironment: 'docker', databaseType: 'mysql', databaseVersion: '8.4');

        self::assertTrue($this->generator->supports($config));
    }

    public function testSupportsReturnsFalseForLocalEnvironment(): void
    {
        $config = $this->createConfiguration(devEnvironment: 'local', databaseType: 'mariadb', databaseVersion: '11.4');

        self::assertFalse($this->generator->supports($config));
    }

    public function testSupportsReturnsFalseWithoutDatabase(): void
    {
        $config = $this->createConfiguration(devEnvironment: 'symfony-cli');

        self::assertFalse($this->generator->supports($config));
    }

    public function testGenerateForSymfonyCliDoesNotIncludePhpService(): void
    {
        $config = $this->createConfiguration(devEnvironment: 'symfony-cli', databaseType: 'mariadb', databaseVersion: '11.4');

        $files = $this->generator->generate($config);

        self::assertArrayHasKey('compose.yaml', $files);
        self::assertArrayHasKey('compose.override.yaml', $files);
        self::assertArrayHasKey('.symfony.local.yaml', $files);
        self::assertArrayNotHasKey('Dockerfile', $files);

        self::assertStringNotContainsString('frankenphp', $files['compose.yaml']);
        self::assertStringContainsString('database:', $files['compose.yaml']);
        self::assertStringContainsString('mailer:', $files['compose.yaml']);
        self::assertStringContainsString('redis:', $files['compose.yaml']);
    }

    public function testGenerateForSymfonyCliIncludesSymfonyLocalYaml(): void
    {
        $config = $this->createConfiguration(devEnvironment: 'symfony-cli', databaseType: 'mariadb', databaseVersion: '11.4');

        $files = $this->generator->generate($config);

        $content = $files['.symfony.local.yaml'];
        self::assertStringContainsString('docker_compose: ~', $content);
        self::assertStringContainsString('messenger_consume_async: ~', $content);
        self::assertStringContainsString('use_gzip: true', $content);
    }

    public function testGenerateForDockerIncludesPhpServiceAndDockerfile(): void
    {
        $config = $this->createConfiguration(devEnvironment: 'docker', databaseType: 'mysql', databaseVersion: '8.4');

        $files = $this->generator->generate($config);

        self::assertArrayHasKey('compose.yaml', $files);
        self::assertArrayHasKey('compose.override.yaml', $files);
        self::assertArrayHasKey('Dockerfile', $files);
        self::assertArrayNotHasKey('.symfony.local.yaml', $files);

        self::assertStringContainsString('php:', $files['compose.yaml']);
        self::assertStringContainsString('dunglas/frankenphp', $files['Dockerfile']);
    }

    #[DataProvider('databaseImageProvider')]
    public function testGenerateUsesCorrectDatabaseImage(string $type, string $version, string $expectedImage): void
    {
        $config = $this->createConfiguration(devEnvironment: 'symfony-cli', databaseType: $type, databaseVersion: $version);

        $files = $this->generator->generate($config);

        self::assertStringContainsString($expectedImage, $files['compose.yaml']);
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function databaseImageProvider(): iterable
    {
        yield 'mariadb' => ['mariadb', '11.4', 'mariadb:11.4'];
        yield 'mysql' => ['mysql', '8.4', 'mysql:8.4'];
        yield 'postgresql' => ['postgresql', '17', 'postgres:17'];
    }

    public function testGenerateMariadbHasMysqlEnvVars(): void
    {
        $config = $this->createConfiguration(devEnvironment: 'symfony-cli', databaseType: 'mariadb', databaseVersion: '11.4');

        $files = $this->generator->generate($config);

        self::assertStringContainsString('MYSQL_ROOT_PASSWORD', $files['compose.yaml']);
        self::assertStringContainsString('MYSQL_DATABASE', $files['compose.yaml']);
    }

    public function testGeneratePostgresqlHasPostgresEnvVars(): void
    {
        $config = $this->createConfiguration(devEnvironment: 'symfony-cli', databaseType: 'postgresql', databaseVersion: '17');

        $files = $this->generator->generate($config);

        self::assertStringContainsString('POSTGRES_DB', $files['compose.yaml']);
        self::assertStringContainsString('POSTGRES_USER', $files['compose.yaml']);
    }

    public function testDockerfileUsesPdoPgsqlForPostgresql(): void
    {
        $config = $this->createConfiguration(devEnvironment: 'docker', databaseType: 'postgresql', databaseVersion: '17');

        $files = $this->generator->generate($config);

        self::assertStringContainsString('pdo_pgsql', $files['Dockerfile']);
        self::assertStringNotContainsString('pdo_mysql', $files['Dockerfile']);
    }

    public function testDockerfileUsesPdoMysqlForMysql(): void
    {
        $config = $this->createConfiguration(devEnvironment: 'docker', databaseType: 'mysql', databaseVersion: '8.4');

        $files = $this->generator->generate($config);

        self::assertStringContainsString('pdo_mysql', $files['Dockerfile']);
        self::assertStringNotContainsString('pdo_pgsql', $files['Dockerfile']);
    }

    public function testOverrideExposesCorrectDatabasePort(): void
    {
        $config = $this->createConfiguration(devEnvironment: 'symfony-cli', databaseType: 'postgresql', databaseVersion: '17');

        $files = $this->generator->generate($config);

        self::assertStringContainsString('5432:5432', $files['compose.override.yaml']);
    }

    public function testOverrideExposesMailpitPorts(): void
    {
        $config = $this->createConfiguration(devEnvironment: 'symfony-cli', databaseType: 'mariadb', databaseVersion: '11.4');

        $files = $this->generator->generate($config);

        self::assertStringContainsString('8025:8025', $files['compose.override.yaml']);
        self::assertStringContainsString('1025:1025', $files['compose.override.yaml']);
    }

    public function testDatabaseHealthcheckForMysql(): void
    {
        $config = $this->createConfiguration(devEnvironment: 'symfony-cli', databaseType: 'mysql', databaseVersion: '8.4');

        $files = $this->generator->generate($config);

        self::assertStringContainsString('healthcheck:', $files['compose.yaml']);
        self::assertStringContainsString('healthcheck.sh', $files['compose.yaml']);
    }

    public function testDatabaseHealthcheckForPostgresql(): void
    {
        $config = $this->createConfiguration(devEnvironment: 'symfony-cli', databaseType: 'postgresql', databaseVersion: '17');

        $files = $this->generator->generate($config);

        self::assertStringContainsString('pg_isready', $files['compose.yaml']);
    }

    public function testRedisHealthcheck(): void
    {
        $config = $this->createConfiguration(devEnvironment: 'symfony-cli', databaseType: 'mariadb', databaseVersion: '11.4');

        $files = $this->generator->generate($config);

        self::assertStringContainsString('redis-cli', $files['compose.yaml']);
    }

    public function testEnvLocalForSymfonyCliUsesLocalhost(): void
    {
        $config = $this->createConfiguration(devEnvironment: 'symfony-cli', databaseType: 'mariadb', databaseVersion: '11.4');

        $files = $this->generator->generate($config);

        self::assertArrayHasKey('.env.local', $files);
        self::assertStringContainsString('127.0.0.1:3306', $files['.env.local']);
        self::assertStringContainsString('smtp://127.0.0.1:1025', $files['.env.local']);
    }

    public function testEnvLocalForDockerUsesServiceNames(): void
    {
        $config = $this->createConfiguration(devEnvironment: 'docker', databaseType: 'postgresql', databaseVersion: '17');

        $files = $this->generator->generate($config);

        self::assertArrayHasKey('.env.local', $files);
        self::assertStringContainsString('database:5432', $files['.env.local']);
        self::assertStringContainsString('smtp://mailer:1025', $files['.env.local']);
        self::assertStringContainsString('redis://redis:6379', $files['.env.local']);
    }

    public function testEnvLocalNotGeneratedForNonSymfonyProject(): void
    {
        $config = $this->createConfiguration(devEnvironment: 'symfony-cli', databaseType: 'mariadb', databaseVersion: '11.4', isSymfonyProject: false);

        $files = $this->generator->generate($config);

        self::assertArrayNotHasKey('.env.local', $files);
    }

    public function testOverrideDockerUsesServiceNameForDatabaseUrl(): void
    {
        $config = $this->createConfiguration(devEnvironment: 'docker', databaseType: 'mysql', databaseVersion: '8.4');

        $files = $this->generator->generate($config);

        self::assertStringContainsString('database:3306', $files['compose.override.yaml']);
        self::assertStringContainsString('redis://redis:6379', $files['compose.override.yaml']);
        self::assertStringNotContainsString('127.0.0.1', $files['compose.override.yaml']);
    }

    public function testGetTargetFilesReturnsExpectedFiles(): void
    {
        $expected = ['compose.yaml', 'compose.override.yaml', 'Dockerfile', '.symfony.local.yaml', '.env.local'];

        self::assertSame($expected, $this->generator->getTargetFiles());
    }

    private function createConfiguration(
        string $devEnvironment = 'local',
        ?string $databaseType = null,
        ?string $databaseVersion = null,
        bool $isSymfonyProject = true,
    ): Configuration {
        return new Configuration(
            projectName: 'test-project',
            phpVersion: '8.4',
            phpstanLevel: 9,
            isSymfonyProject: $isSymfonyProject,
            symfonyVersion: '8.0',
            ciProvider: 'none',
            devEnvironment: $devEnvironment,
            includeMakefile: false,
            includePhpCsFixer: false,
            includePhpStan: false,
            includeRector: false,
            includePhpUnit: false,
            srcPath: 'src',
            testsPath: 'tests',
            outputDir: '.',
            conflictResolution: ConflictResolution::ASK,
            databaseType: $databaseType,
            databaseVersion: $databaseVersion,
        );
    }
}
