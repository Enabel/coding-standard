# AGENTS.md

Instructions for AI agents working on this project.

## Language

- Commit messages must be in English
- Conventional commits: `feat:`, `fix:`, `chore:`, `refactor:`, `test:`, `docs:`
- Do not add "Co-Authored-By" trailers to commit messages
- Do not mention Claude or AI in commit messages or PR descriptions

## Project Analysis

Before running any command, analyze the project setup (Docker, Composer, Makefile, compose.yaml) to determine how to execute PHP:
- Symfony CLI: `symfony php ...`, `symfony console ...`
- Docker Compose: `docker compose exec php ...`
- Makefile: check available `make` targets first

<?php if ($isSymfony): ?>
## Architecture & Patterns

### Controllers
- Controllers MUST be invokable: one action = one controller
- Use PHP attributes for routing (`#[Route]`)

### Entities
- Use ULID as identifier (`#[ORM\CustomIdGenerator(class: UlidGenerator::class)]`)
- Use immutable timestamps for date fields (e.g. `\DateTimeImmutable` for `createdAt`, `updatedAt`)
- Implement `TimestampableInterface` or use Doctrine lifecycle callbacks for automatic timestamps

### Messenger
- Use Symfony Messenger for long-running or non-critical tasks
- Prefer asynchronous transport for anything that doesn't need an immediate response

### File Management
- Use Flysystem (`league/flysystem-bundle`) for file storage abstraction
- Never write directly to the filesystem — always go through the abstraction layer

### Images
- Use LiipImagineBundle for image processing (resizing, thumbnails, optimization)
- Define standard filter sets for consistent image formats across the project

## Frontend

### CSS & Styling
- Never use inline CSS
- Use the existing theme or CSS framework — do not add custom CSS unless strictly necessary
- Maintain UX consistency with what is already in place

### JavaScript
- No JS frameworks. Use vanilla JavaScript with Stimulus (Symfony UX)
- For dynamic/interactive blocks, use Twig Live Components (`symfony/ux-live-component`)

### Twig
- Use Twig Components (`symfony/ux-twig-component`) for reusable UI blocks
- Do not use Twig macros — prefer components
- For dynamic interactivity, use Live Components

<?php endif; ?>
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
- Test directory structure must mirror `<?= $srcPath ?>/` under `<?= $testsPath ?>/`
- Prefer integration tests when possible, unit tests for isolated logic

## What to Avoid

- Over-engineering when a simple solution exists
- Suggesting JS frameworks when working in a PHP/Symfony context
- Adding unnecessary abstractions for one-time operations
- Ignoring existing project conventions
