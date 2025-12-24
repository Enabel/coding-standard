# Enabel Coding Standard

A CLI tool to initialize coding standards configuration for PHP/Symfony projects. Generates ready-to-use configuration files for code quality tools, CI/CD pipelines, and development environments.

## Installation

```bash
composer require enabel/coding-standard --dev
```

## Usage

### Interactive Mode

Run the init command without options for interactive configuration:

```bash
vendor/bin/coding-standard init
```

You'll be prompted to configure:
- Project name and PHP version
- Symfony version (if applicable)
- Database type and version (MariaDB, MySQL, PostgreSQL)
- Code quality tools (PHP-CS-Fixer, PHPStan, Rector, PHPUnit)
- CI provider (GitLab CI, GitHub Actions, Azure DevOps)
- Development environment (DDEV, Makefile)

### Non-Interactive Mode

Use CLI options for automated setup:

```bash
vendor/bin/coding-standard init \
    --project-name=my-project \
    --php-version=8.4 \
    --symfony=8.0 \
    --database=postgresql \
    --database-version=17 \
    --ci=gitlab \
    --no-interaction
```

### CLI Options

| Option | Description | Default |
|--------|-------------|---------|
| `--project-name` | Project name | Current directory name |
| `--php-version` | PHP version (8.3, 8.4, 8.5) | 8.4 |
| `--symfony` | Symfony version (7.4, 8.0) or "no" | no |
| `--database` | Database type (mariadb, mysql, postgresql) | - |
| `--database-version` | Database version | - |
| `--ci` | CI provider (gitlab, github, azure, none) | none |
| `--php-cs-fixer` / `--no-php-cs-fixer` | Include PHP-CS-Fixer | yes |
| `--phpstan` / `--no-phpstan` | Include PHPStan | yes |
| `--phpstan-level` | PHPStan level (6-9 or max) | max |
| `--rector` / `--no-rector` | Include Rector | yes |
| `--phpunit` / `--no-phpunit` | Include PHPUnit config | yes |
| `--ddev` / `--no-ddev` | Include DDEV configuration | yes |
| `--makefile` / `--no-makefile` | Include Makefile | yes |
| `--src-path` | Source directory path | src |
| `--tests-path` | Tests directory path | tests |
| `--force` | Overwrite existing files | no |
| `--skip-existing` | Skip existing files | no |
| `--output-dir` | Output directory | . |

## Generated Files

### Code Quality Tools

| File | Description |
|------|-------------|
| `.php-cs-fixer.dist.php` | PHP-CS-Fixer configuration |
| `phpstan.neon` | PHPStan configuration |
| `rector.php` | Rector configuration |
| `phpunit.dist.xml` | PHPUnit configuration |
| `tools/php-cs-fixer/composer.json` | Isolated PHP-CS-Fixer dependencies |
| `tools/phpstan/composer.json` | Isolated PHPStan dependencies |
| `tools/rector/composer.json` | Isolated Rector dependencies |

### CI/CD Pipelines

| File | Description |
|------|-------------|
| `.gitlab-ci.yml` | GitLab CI pipeline |
| `.github/workflows/ci.yml` | GitHub Actions workflow |
| `azure-pipelines.yml` | Azure DevOps pipeline |

### Development Environment

| File | Description |
|------|-------------|
| `.ddev/config.yaml` | DDEV configuration |
| `Makefile` | Development commands |
| `composer-scripts.json` | Composer scripts configuration |

## Database Support

The following databases are supported with their recent versions:

| Database | Versions |
|----------|----------|
| MariaDB | 11.4, 10.11, 10.6 |
| MySQL | 8.4, 8.0, 5.7 |
| PostgreSQL | 17, 16, 15 |

When a database is configured:
- CI pipelines include a database service container
- Correct PHP extensions are installed (pdo_mysql or pdo_pgsql)
- DATABASE_URL is configured for Doctrine

## Post-Installation

After running `init`:

### 1. Install Tool Dependencies

```bash
composer install -d tools/php-cs-fixer
composer install -d tools/phpstan
composer install -d tools/rector
```

Or use the Makefile:

```bash
make install
```

### 2. Configure Database Connection (Symfony projects)

If you configured a database, you need to set the `DATABASE_URL` environment variable in your `.env.local` file:

**MariaDB:**
```dotenv
DATABASE_URL="mysql://user:password@127.0.0.1:3306/my_database?serverVersion=11.4-MariaDB"
```

**MySQL:**
```dotenv
DATABASE_URL="mysql://user:password@127.0.0.1:3306/my_database?serverVersion=8.4"
```

**PostgreSQL:**
```dotenv
DATABASE_URL="postgresql://user:password@127.0.0.1:5432/my_database?serverVersion=17&charset=utf8"
```

When using DDEV, the database is automatically configured. Use `ddev describe` to see the connection details, or use the DDEV-provided environment variables:

```dotenv
# DDEV automatically sets these, but you can override in .env.local if needed
DATABASE_URL="mysql://db:db@db:3306/db?serverVersion=11.4-MariaDB"
```

> **Note:** The CI pipelines are pre-configured with test database credentials. The `DATABASE_URL` in CI uses `db` as the database name because Symfony automatically appends `_test` suffix in test environment.

## Available Make Commands

| Command | Description |
|---------|-------------|
| `make help` | Show available commands |
| `make install` | Install all dependencies |
| `make lint` | Run all linters |
| `make analyze` | Run static analysis |
| `make test` | Run tests |
| `make ci` | Run full CI pipeline |
| `make csf` | Check code style |
| `make csf-fix` | Fix code style |
| `make stan` | Run PHPStan |
| `make rector` | Check Rector (dry-run) |
| `make rector-fix` | Apply Rector fixes |

## License

MIT
