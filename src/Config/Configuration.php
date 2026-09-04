<?php

declare(strict_types=1);

/*
 * This file is part of the Enabel Coding Standard.
 * Copyright (c) Enabel <https://github.com/Enabel>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enabel\CodingStandard\Config;

final readonly class Configuration
{
    /**
     * PHP versions offered for new projects: only those still supported by Upsun
     * (8.3 and below are end of life).
     *
     * @see https://developer.upsun.com/docs/languages/php
     */
    public const array PHP_VERSIONS = ['8.4', '8.5'];

    public const string DEFAULT_PHP_VERSION = '8.4';

    /**
     * MariaDB is the only database offered for new projects.
     *
     * MySQL and PostgreSQL are still handled when detected in an existing project
     * (see ProjectDetector::detectDatabase()), but never proposed for a new one.
     */
    public const string DEFAULT_DATABASE_TYPE = 'mariadb';

    /**
     * Versions currently supported by Upsun (retired versions are not offered).
     *
     * MySQL and PostgreSQL entries are only used for existing projects where such
     * a database is detected.
     *
     * @see https://developer.upsun.com/docs/add-services/mysql
     * @see https://developer.upsun.com/docs/add-services/postgresql
     */
    public const array DATABASE_VERSIONS = [
        'mariadb' => ['12.3', '11.8', '11.4'],
        'mysql' => ['8.4'],
        'postgresql' => ['18', '17', '16', '15', '14'],
    ];

    /**
     * Recommended version per database type: the most recent LTS rather than
     * the most recent release (MariaDB 12.3 is a short-term rolling release).
     */
    public const array DEFAULT_DATABASE_VERSIONS = [
        'mariadb' => '11.8',
        'mysql' => '8.4',
        'postgresql' => '18',
    ];

    public const array DEV_ENVIRONMENTS = [
        'symfony-cli' => 'Symfony CLI',
        'docker' => 'Docker Compose',
        'local' => 'Local PHP',
    ];

    public function __construct(
        public string $projectName,
        public string $phpVersion,
        public int $phpstanLevel,
        public bool $isSymfonyProject,
        public ?string $symfonyVersion,
        public string $ciProvider,
        public string $devEnvironment,
        public bool $includeMakefile,
        public bool $includePhpCsFixer,
        public bool $includePhpStan,
        public bool $includeRector,
        public bool $includePhpUnit,
        public string $srcPath,
        public string $testsPath,
        public string $outputDir,
        public ConflictResolution $conflictResolution,
        public ?string $databaseType = null,
        public ?string $databaseVersion = null,
        public bool $includeDbAdmin = false,
        public bool $includeFoundry = false,
    ) {
    }

    public function getPhpVersionNumber(): string
    {
        return str_replace('.', '', $this->phpVersion);
    }

    public function hasAnyTool(): bool
    {
        return $this->includePhpCsFixer
            || $this->includePhpStan
            || $this->includeRector;
    }

    public function hasAnyCi(): bool
    {
        return 'none' !== $this->ciProvider;
    }

    /**
     * Returns the recommended version for the given database type.
     */
    public static function getDefaultDatabaseVersion(string $databaseType): ?string
    {
        return self::DEFAULT_DATABASE_VERSIONS[$databaseType] ?? null;
    }

    public function hasDatabase(): bool
    {
        return null !== $this->databaseType && null !== $this->databaseVersion;
    }

    public function getDatabaseImage(): string
    {
        if (!$this->hasDatabase()) {
            return '';
        }

        return match ($this->databaseType) {
            'mariadb' => sprintf('mariadb:%s', $this->databaseVersion),
            'mysql' => sprintf('mysql:%s', $this->databaseVersion),
            'postgresql' => sprintf('postgres:%s', $this->databaseVersion),
            default => '',
        };
    }

    public function getDatabasePort(): int
    {
        return match ($this->databaseType) {
            'postgresql' => 5432,
            default => 3306,
        };
    }

    public function getDatabaseUrl(string $host = '127.0.0.1'): string
    {
        if (!$this->hasDatabase()) {
            return '';
        }

        $port = $this->getDatabasePort();

        return match ($this->databaseType) {
            'mariadb' => sprintf('mysql://root@%s:%d/app?serverVersion=%s-MariaDB', $host, $port, $this->databaseVersion),
            'mysql' => sprintf('mysql://root@%s:%d/app?serverVersion=%s', $host, $port, $this->databaseVersion),
            'postgresql' => sprintf('postgresql://root@%s:%d/app?serverVersion=%s', $host, $port, $this->databaseVersion),
            default => '',
        };
    }

    /**
     * @return array<string, string>
     */
    public function getDatabaseEnvVars(): array
    {
        return match ($this->databaseType) {
            'mariadb', 'mysql' => [
                'MYSQL_DATABASE' => 'app',
                'MYSQL_PASSWORD' => 'password123',
                'MYSQL_ALLOW_EMPTY_PASSWORD' => 'yes',
            ],
            'postgresql' => [
                'POSTGRES_DB' => 'app',
                'POSTGRES_PASSWORD' => 'password123',
                'POSTGRES_HOST_AUTH_METHOD' => 'trust',
            ],
            default => [],
        };
    }

    public function getPhpDatabaseExtension(): string
    {
        return match ($this->databaseType) {
            'postgresql' => 'pgsql',
            default => 'mysql',
        };
    }

    public function usesDocker(): bool
    {
        return in_array($this->devEnvironment, ['symfony-cli', 'docker'], true);
    }
}
