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
    public const array DATABASE_VERSIONS = [
        'mariadb' => ['11.4', '10.11', '10.6'],
        'mysql' => ['8.4', '8.0', '5.7'],
        'postgresql' => ['17', '16', '15'],
    ];

    public function __construct(
        public string $projectName,
        public string $phpVersion,
        public int $phpstanLevel,
        public bool $isSymfonyProject,
        public ?string $symfonyVersion,
        public string $ciProvider,
        public bool $includeDdev,
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

    public function getDatabaseUrl(): string
    {
        if (!$this->hasDatabase()) {
            return '';
        }

        $host = '127.0.0.1';
        $port = $this->getDatabasePort();

        return match ($this->databaseType) {
            'mariadb' => sprintf('mysql://db:db@%s:%d/db?serverVersion=%s-MariaDB', $host, $port, $this->databaseVersion),
            'mysql' => sprintf('mysql://db:db@%s:%d/db?serverVersion=%s', $host, $port, $this->databaseVersion),
            'postgresql' => sprintf('postgresql://db:db@%s:%d/db?serverVersion=%s', $host, $port, $this->databaseVersion),
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
                'MYSQL_ROOT_PASSWORD' => 'root',
                'MYSQL_DATABASE' => 'db_test',
                'MYSQL_USER' => 'db',
                'MYSQL_PASSWORD' => 'db',
            ],
            'postgresql' => [
                'POSTGRES_DB' => 'db_test',
                'POSTGRES_USER' => 'db',
                'POSTGRES_PASSWORD' => 'db',
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
}
