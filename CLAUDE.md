# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Enabel Coding Standard is a PHP CLI tool that generates configuration files for PHP/Symfony projects. It initializes code quality tools (PHP-CS-Fixer, PHPStan, Rector, PHPUnit), CI/CD pipelines (GitLab CI, GitHub Actions, Azure DevOps), and development environments (DDEV, Makefile).

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
- `DdevGenerator`, `MakefileGenerator`, `ComposerScriptsGenerator` - Dev environment

**Key Classes:**
- `Configuration` - Contains database type/version constants and helper methods (`getDbServerVersion()`, `getDbPdoExtension()`)
- `ConflictResolution` - Enum for handling existing files (Overwrite, Skip, Ask)
- `TemplateRenderer` - Simple template engine, throws `RuntimeException` on errors

## Adding New Generators

1. Create class in `src/Generator/` extending `AbstractGenerator`
2. Implement `supports()` to check if generator should run based on `Configuration`
3. Implement `generate()` using `$this->render()` and `$this->filesystem->dumpFile()`
4. Add template in `/templates/`
5. Register in `InitCommand::getGenerators()`

## Namespace

`Enabel\CodingStandard\` → `src/`