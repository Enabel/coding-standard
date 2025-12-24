# Executables
DDEV       = ddev
EXEC       = $(DDEV) exec
PHP        = $(EXEC) php
COMPOSER   = $(EXEC) composer
<?php if ($isSymfony): ?>
SYMFONY    = $(EXEC) bin/console
<?php endif; ?>

# Misc
.DEFAULT_GOAL = help

## —— Help ————————————————————————————————————————————————————————————————
.PHONY: help
help: ## Display this help
	@grep -E '(^[a-zA-Z_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-20s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'

## —— Project —————————————————————————————————————————————————————————————
.PHONY: install run abort restart

install: ## Install project dependencies
	$(COMPOSER) install
<?php if ($isSymfony): ?>
	$(SYMFONY) doctrine:migrations:migrate --no-interaction
<?php endif; ?>

run: ## Start DDEV
	$(DDEV) start

abort: ## Stop DDEV
	$(DDEV) stop

restart: abort run ## Restart DDEV

## —— Composer ————————————————————————————————————————————————————————————
.PHONY: composer-install composer-update composer-validate

composer-install: ## Install Composer dependencies
	$(COMPOSER) install

composer-update: ## Update Composer dependencies
	$(COMPOSER) update

composer-validate: ## Validate composer.json
	$(COMPOSER) validate --strict

<?php if ($isSymfony): ?>
## —— Symfony —————————————————————————————————————————————————————————————
.PHONY: cc

cc: ## Clear cache
	$(SYMFONY) cache:clear

## —— Database ————————————————————————————————————————————————————————————
.PHONY: db-create db-drop db-migrate db-diff db-reset

db-create: ## Create database
	$(SYMFONY) doctrine:database:create --if-not-exists

db-drop: ## Drop database (with confirmation)
	$(SYMFONY) doctrine:database:drop --force --if-exists

db-migrate: ## Run migrations
	$(SYMFONY) doctrine:migrations:migrate --no-interaction

db-diff: ## Generate migration from entity changes
	$(SYMFONY) make:migration

db-reset: db-drop db-create db-migrate ## Reset database

## —— Linters —————————————————————————————————————————————————————————————
.PHONY: lint lint-yaml lint-container lint-doctrine lint-composer lint-twig

lint: lint-yaml lint-container lint-doctrine lint-composer lint-twig ## Run all linters

lint-yaml: ## Lint YAML files
	$(SYMFONY) lint:yaml config --parse-tags

lint-container: ## Lint Symfony container
	$(SYMFONY) lint:container

lint-doctrine: ## Validate Doctrine schema
	$(SYMFONY) doctrine:schema:validate --skip-sync -v --no-interaction

lint-composer: ## Validate composer.json
	$(COMPOSER) validate --no-check-publish

lint-twig: ## Lint Twig templates
	$(SYMFONY) lint:twig templates

<?php endif; ?>
## —— Code Quality ————————————————————————————————————————————————————————
.PHONY: csf csf-fix stan rector rector-fix analyze fix

<?php if ($includePhpCsFixer): ?>
csf: ## Check code style (dry-run)
	$(PHP) tools/php-cs-fixer/vendor/bin/php-cs-fixer fix --dry-run --diff

csf-fix: ## Fix code style
	$(PHP) tools/php-cs-fixer/vendor/bin/php-cs-fixer fix

<?php endif; ?>
<?php if ($includePhpStan): ?>
stan: ## Run PHPStan
	$(PHP) tools/phpstan/vendor/bin/phpstan analyse

<?php endif; ?>
<?php if ($includeRector): ?>
rector: ## Run Rector (dry-run)
	$(PHP) tools/rector/vendor/bin/rector process --dry-run

rector-fix: ## Run Rector and apply changes
	$(PHP) tools/rector/vendor/bin/rector process

<?php endif; ?>
<?php if ($includePhpCsFixer && $includePhpStan): ?>
analyze: csf stan ## Run code style check and PHPStan

<?php endif; ?>
<?php if ($includePhpCsFixer): ?>
fix: csf-fix ## Fix code style issues

<?php endif; ?>
## —— Tests ———————————————————————————————————————————————————————————————
.PHONY: test test-coverage

test: ## Run PHPUnit tests
	$(EXEC) bin/phpunit

test-coverage: ## Run tests with coverage report
	$(EXEC) bin/phpunit --coverage-html var/coverage --coverage-text

## —— CI ——————————————————————————————————————————————————————————————————
.PHONY: ci qa

<?php if ($isSymfony): ?>
ci: lint analyze test ## Run full CI pipeline (lint + analyze + test)

<?php else: ?>
ci: analyze test ## Run full CI pipeline (analyze + test)

<?php endif; ?>
qa: csf stan test ## Run QA checks
