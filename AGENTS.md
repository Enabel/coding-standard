# AGENTS.md

Instructions for AI agents working on this project.

## Project Overview

Enabel Coding Standard is a PHP CLI tool that generates configuration files for PHP/Symfony projects. It initializes code quality tools (PHP-CS-Fixer, PHPStan, Rector, PHPUnit), CI/CD pipelines (GitLab CI, GitHub Actions, Azure DevOps), and development environments (Docker Compose, Makefile).

## Language

- Commit messages must be in English
- Conventional commits: `feat:`, `fix:`, `chore:`, `refactor:`, `test:`, `docs:`
- Do not add "Co-Authored-By" trailers to commit messages
- Do not mention Claude or AI in commit messages or PR descriptions

## Commands

```bash
# Install dependencies
composer install

# Run the CLI tool (after installation)
vendor/bin/coding-standard init              # Interactive mode
vendor/bin/coding-standard init --no-interaction [options]  # Non-interactive

# Run tests
vendor/bin/phpunit
```

## Project Analysis

Before running any command, analyze the project setup (Docker, Composer, Makefile, compose.yaml) to determine how to execute PHP:
- Symfony CLI: `symfony php ...`, `symfony console ...`
- Docker Compose: `docker compose exec php ...`
- Makefile: check available `make` targets first

## Architecture

**Entry Point:** `bin/coding-standard` → `Application.php` → `InitCommand.php`

**Core Flow:**
1. `InitCommand` orchestrates the process using Symfony Console
2. `InteractiveIO` handles user input collection (interactive mode)
3. `Configuration` (readonly data class) holds all settings, including database version mappings
4. `ExistingConfigDetector` checks for file conflicts
5. Generators produce output files using `TemplateRenderer`

**Generator Pattern:**
- All generators extend `AbstractGenerator` and implement `GeneratorInterface`
- Each generator has `generate(Configuration $config)` and `supports(Configuration $config)` methods
- Templates in `/templates/` directory use PHP's `extract()` + `include` for variable injection

**Generators (src/Generator/):**
- `PhpCsFixerGenerator`, `PhpStanGenerator`, `RectorGenerator`, `PhpUnitGenerator` - Code quality tools
- `GitLabCiGenerator`, `GitHubActionsGenerator`, `AzureDevOpsGenerator` - CI pipelines
- `DockerComposeGenerator`, `MakefileGenerator`, `ComposerScriptsGenerator` - Dev environment

**Key Classes:**
- `Configuration` - Contains database type/version constants and helper methods (`getDbServerVersion()`, `getDbPdoExtension()`)
- `ConflictResolution` - Enum for handling existing files (Overwrite, Skip, Ask)
- `TemplateRenderer` - Simple template engine, throws `RuntimeException` on errors

**Namespace:** `Enabel\CodingStandard\` → `src/`

## Adding New Generators

1. Create class in `src/Generator/` extending `AbstractGenerator`
2. Implement `supports()` to check if generator should run based on `Configuration`
3. Implement `generate()` using `$this->render()` and `$this->filesystem->dumpFile()`
4. Add template in `/templates/`
5. Register in `InitCommand::getGenerators()`

## Development Workflow

### Adding a Feature
1. Write an implementation plan first
2. Follow TDD: write the test, then write the code to make it pass
3. Run the full test suite before committing

### Code Quality
- Use the configured tools (PHPStan, PHP-CS-Fixer, Rector) — run them, fix issues
- Do not add `@phpstan-ignore` or baseline entries to silence errors — fix the root cause
- Do not disable PHP-CS-Fixer rules — adapt your code to the standard

### Testing
- Use mocks and stubs appropriately
- Test directory structure must mirror `src/` under `tests/`
- Prefer integration tests when possible, unit tests for isolated logic

## What to Avoid

- Over-engineering when a simple solution exists
- Suggesting JS frameworks when working in a PHP/Symfony context
- Adding unnecessary abstractions for one-time operations
- Ignoring existing project conventions
