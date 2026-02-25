<!-- BEGIN ENABEL CODING STANDARD -->
## Project Conventions

### Language

- Commit messages must be in English
- Conventional commits: `feat:`, `fix:`, `chore:`, `refactor:`, `test:`, `docs:`
- Do not add "Co-Authored-By" trailers to commit messages
- Do not mention Claude or AI in commit messages or PR descriptions

### Project Analysis

Before running any command, analyze the project setup (Docker, Composer, Makefile, compose.yaml) to determine how to execute PHP:
- Symfony CLI: `symfony php ...`, `symfony console ...`
- Docker Compose: `docker compose exec php ...`
- Makefile: check available `make` targets first

<?php if ($isSymfony): ?>
### Architecture & Patterns

**Controllers:**
- Controllers MUST be invokable: one action = one controller
- Use PHP attributes for routing (`#[Route]`)

**Entities:**
- Use ULID as identifier (`#[ORM\CustomIdGenerator(class: UlidGenerator::class)]`)
- Use immutable timestamps for date fields (e.g. `\DateTimeImmutable` for `createdAt`, `updatedAt`)
- Implement `TimestampableInterface` or use Doctrine lifecycle callbacks for automatic timestamps

**Messenger:**
- Use Symfony Messenger for long-running or non-critical tasks
- Prefer asynchronous transport for anything that doesn't need an immediate response

**File Management:**
- Use Flysystem (`league/flysystem-bundle`) for file storage abstraction
- Never write directly to the filesystem — always go through the abstraction layer

**Images:**
- Use LiipImagineBundle for image processing (resizing, thumbnails, optimization)
- Define standard filter sets for consistent image formats across the project

### Frontend

- Never use inline CSS — use the existing theme or CSS framework
- Maintain UX consistency with what is already in place
- No JS frameworks. Use vanilla JavaScript with Stimulus (Symfony UX)
- Use Twig Components (`symfony/ux-twig-component`) for reusable UI blocks — do not use Twig macros
- For dynamic interactivity, use Live Components (`symfony/ux-live-component`)

<?php endif; ?>
### Development Workflow

- Write an implementation plan before starting a feature
- Follow TDD: write the test first, then the code to make it pass
- Use the configured tools (PHPStan, PHP-CS-Fixer, Rector) — do not add `@phpstan-ignore` or disable rules
- Test directory structure must mirror `<?= $srcPath ?>/` under `<?= $testsPath ?>/`

### What to Avoid

- Over-engineering when a simple solution exists
- Suggesting JS frameworks when working in a PHP/Symfony context
- Adding unnecessary abstractions for one-time operations
- Ignoring existing project conventions
<!-- END ENABEL CODING STANDARD -->
