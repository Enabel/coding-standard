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
- MariaDB version (MariaDB is the only database offered for new projects)
- Code quality tools (PHP-CS-Fixer, PHPStan, Rector, PHPUnit)
- CI provider (GitLab CI, GitHub Actions, Azure DevOps)
- Development environment (Symfony CLI, Docker Compose, Local PHP)

### Non-Interactive Mode

Use CLI options for automated setup:

```bash
vendor/bin/coding-standard init \
    --project-name=my-project \
    --php-version=8.4 \
    --symfony=8.0 \
    --database-version=11.8 \
    --ci=gitlab \
    --no-interaction
```

### CLI Options

| Option | Description | Default |
|--------|-------------|---------|
| `--project-name` | Project name | Current directory name |
| `--php-version` | PHP version (8.4, 8.5) | 8.4 |
| `--symfony` | Symfony version (7.4, 8.0) or "no" | no |
| `--database` / `--no-database` | Configure a MariaDB database (Symfony projects only) | yes |
| `--database-version` | MariaDB version (12.3, 11.8, 11.4) | 11.8 |
| `--ci` | CI provider (gitlab, github, azure, none) | none |
| `--php-cs-fixer` / `--no-php-cs-fixer` | Include PHP-CS-Fixer | yes |
| `--phpstan` / `--no-phpstan` | Include PHPStan | yes |
| `--phpstan-level` | PHPStan level (6-9 or max) | max |
| `--rector` / `--no-rector` | Include Rector | yes |
| `--phpunit` / `--no-phpunit` | Include PHPUnit config | yes |
| `--dev-env` | Development environment (symfony-cli, docker, local) | symfony-cli |
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
| `compose.yaml` | Docker Compose services |
| `compose.override.yaml` | Docker Compose local overrides |
| `Dockerfile` | PHP container (docker env only) |
| `Makefile` | Development commands |
| `composer-scripts.json` | Composer scripts configuration |

## Database Support

**New projects always use MariaDB.** Only versions still supported by
[Upsun](https://developer.upsun.com/docs/add-services/mysql) are offered:

| Versions | Default |
|----------|---------|
| 12.3, 11.8, 11.4 | 11.8 (LTS — 12.3 is a short-term rolling release) |

MySQL and PostgreSQL are not offered by `init`, but `ci:add` and `ci:update` still detect them in an
existing `compose.yaml` and generate the matching pipeline (MySQL 8.4, PostgreSQL 18 to 14).

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

```dotenv
DATABASE_URL="mysql://user:password@127.0.0.1:3306/my_database?serverVersion=11.8-MariaDB"
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
