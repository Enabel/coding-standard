<?php

declare(strict_types=1);

/*
 * This file is part of the Enabel Coding Standard.
 * Copyright (c) Enabel <https://github.com/Enabel>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enabel\CodingStandard\Tests\Config;

use Enabel\CodingStandard\Config\Configuration;
use Enabel\CodingStandard\Config\ConflictResolution;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ConfigurationTest extends TestCase
{
    public function testGetPhpVersionNumber(): void
    {
        $config = $this->createConfiguration(phpVersion: '8.3');

        self::assertSame('83', $config->getPhpVersionNumber());
    }

    public function testHasAnyToolReturnsTrueWhenPhpCsFixerEnabled(): void
    {
        $config = $this->createConfiguration(includePhpCsFixer: true);

        self::assertTrue($config->hasAnyTool());
    }

    public function testHasAnyToolReturnsTrueWhenPhpStanEnabled(): void
    {
        $config = $this->createConfiguration(includePhpStan: true);

        self::assertTrue($config->hasAnyTool());
    }

    public function testHasAnyToolReturnsTrueWhenRectorEnabled(): void
    {
        $config = $this->createConfiguration(includeRector: true);

        self::assertTrue($config->hasAnyTool());
    }

    public function testHasAnyToolReturnsFalseWhenNoToolEnabled(): void
    {
        $config = $this->createConfiguration();

        self::assertFalse($config->hasAnyTool());
    }

    public function testHasAnyCiReturnsTrueWhenCiProviderSet(): void
    {
        $config = $this->createConfiguration(ciProvider: 'github');

        self::assertTrue($config->hasAnyCi());
    }

    public function testHasAnyCiReturnsFalseWhenCiProviderIsNone(): void
    {
        $config = $this->createConfiguration(ciProvider: 'none');

        self::assertFalse($config->hasAnyCi());
    }

    public function testHasDatabaseReturnsTrueWhenBothTypeAndVersionSet(): void
    {
        $config = $this->createConfiguration(databaseType: 'mysql', databaseVersion: '8.0');

        self::assertTrue($config->hasDatabase());
    }

    public function testHasDatabaseReturnsFalseWhenTypeIsNull(): void
    {
        $config = $this->createConfiguration(databaseType: null, databaseVersion: '8.0');

        self::assertFalse($config->hasDatabase());
    }

    public function testHasDatabaseReturnsFalseWhenVersionIsNull(): void
    {
        $config = $this->createConfiguration(databaseType: 'mysql', databaseVersion: null);

        self::assertFalse($config->hasDatabase());
    }

    #[DataProvider('databaseImageProvider')]
    public function testGetDatabaseImage(?string $type, ?string $version, string $expected): void
    {
        $config = $this->createConfiguration(databaseType: $type, databaseVersion: $version);

        self::assertSame($expected, $config->getDatabaseImage());
    }

    /**
     * @return iterable<string, array{?string, ?string, string}>
     */
    public static function databaseImageProvider(): iterable
    {
        yield 'mariadb' => ['mariadb', '10.11', 'mariadb:10.11'];
        yield 'mysql' => ['mysql', '8.0', 'mysql:8.0'];
        yield 'postgresql' => ['postgresql', '16', 'postgres:16'];
        yield 'no database' => [null, null, ''];
    }

    #[DataProvider('databasePortProvider')]
    public function testGetDatabasePort(?string $type, int $expected): void
    {
        $config = $this->createConfiguration(databaseType: $type);

        self::assertSame($expected, $config->getDatabasePort());
    }

    /**
     * @return iterable<string, array{?string, int}>
     */
    public static function databasePortProvider(): iterable
    {
        yield 'mariadb' => ['mariadb', 3306];
        yield 'mysql' => ['mysql', 3306];
        yield 'postgresql' => ['postgresql', 5432];
        yield 'null' => [null, 3306];
    }

    #[DataProvider('databaseUrlProvider')]
    public function testGetDatabaseUrl(?string $type, ?string $version, string $expected): void
    {
        $config = $this->createConfiguration(databaseType: $type, databaseVersion: $version);

        self::assertSame($expected, $config->getDatabaseUrl());
    }

    /**
     * @return iterable<string, array{?string, ?string, string}>
     */
    public static function databaseUrlProvider(): iterable
    {
        yield 'mariadb' => ['mariadb', '10.11', 'mysql://db:db@127.0.0.1:3306/db?serverVersion=10.11-MariaDB'];
        yield 'mysql' => ['mysql', '8.0', 'mysql://db:db@127.0.0.1:3306/db?serverVersion=8.0'];
        yield 'postgresql' => ['postgresql', '16', 'postgresql://db:db@127.0.0.1:5432/db?serverVersion=16'];
        yield 'no database' => [null, null, ''];
    }

    public function testGetDatabaseEnvVarsForMysql(): void
    {
        $config = $this->createConfiguration(databaseType: 'mysql', databaseVersion: '8.0');

        $expected = [
            'MYSQL_ROOT_PASSWORD' => 'root',
            'MYSQL_DATABASE' => 'db_test',
            'MYSQL_USER' => 'db',
            'MYSQL_PASSWORD' => 'db',
        ];

        self::assertSame($expected, $config->getDatabaseEnvVars());
    }

    public function testGetDatabaseEnvVarsForPostgresql(): void
    {
        $config = $this->createConfiguration(databaseType: 'postgresql', databaseVersion: '16');

        $expected = [
            'POSTGRES_DB' => 'db_test',
            'POSTGRES_USER' => 'db',
            'POSTGRES_PASSWORD' => 'db',
        ];

        self::assertSame($expected, $config->getDatabaseEnvVars());
    }

    #[DataProvider('phpDatabaseExtensionProvider')]
    public function testGetPhpDatabaseExtension(?string $type, string $expected): void
    {
        $config = $this->createConfiguration(databaseType: $type);

        self::assertSame($expected, $config->getPhpDatabaseExtension());
    }

    /**
     * @return iterable<string, array{?string, string}>
     */
    public static function phpDatabaseExtensionProvider(): iterable
    {
        yield 'mariadb' => ['mariadb', 'mysql'];
        yield 'mysql' => ['mysql', 'mysql'];
        yield 'postgresql' => ['postgresql', 'pgsql'];
        yield 'null' => [null, 'mysql'];
    }

    private function createConfiguration(
        string $projectName = 'test-project',
        string $phpVersion = '8.2',
        int $phpstanLevel = 5,
        bool $isSymfonyProject = false,
        ?string $symfonyVersion = null,
        string $ciProvider = 'none',
        bool $includeDdev = false,
        bool $includeMakefile = false,
        bool $includePhpCsFixer = false,
        bool $includePhpStan = false,
        bool $includeRector = false,
        bool $includePhpUnit = false,
        string $srcPath = 'src',
        string $testsPath = 'tests',
        string $outputDir = '.',
        ConflictResolution $conflictResolution = ConflictResolution::ASK,
        ?string $databaseType = null,
        ?string $databaseVersion = null,
    ): Configuration {
        return new Configuration(
            projectName: $projectName,
            phpVersion: $phpVersion,
            phpstanLevel: $phpstanLevel,
            isSymfonyProject: $isSymfonyProject,
            symfonyVersion: $symfonyVersion,
            ciProvider: $ciProvider,
            includeDdev: $includeDdev,
            includeMakefile: $includeMakefile,
            includePhpCsFixer: $includePhpCsFixer,
            includePhpStan: $includePhpStan,
            includeRector: $includeRector,
            includePhpUnit: $includePhpUnit,
            srcPath: $srcPath,
            testsPath: $testsPath,
            outputDir: $outputDir,
            conflictResolution: $conflictResolution,
            databaseType: $databaseType,
            databaseVersion: $databaseVersion,
        );
    }
}
