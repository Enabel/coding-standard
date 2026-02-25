# Executables
<?php if ($devEnvironment === 'symfony-cli'): ?>
SYMFONY_CLI = symfony
PHP         = $(SYMFONY_CLI) php
COMPOSER    = $(SYMFONY_CLI) composer
<?php if ($isSymfony): ?>
SYMFONY     = $(SYMFONY_CLI) console
<?php endif; ?>
<?php elseif ($devEnvironment === 'docker'): ?>
DOCKER      = docker compose
EXEC        = $(DOCKER) exec php
PHP         = $(EXEC) php
COMPOSER    = $(EXEC) composer
<?php if ($isSymfony): ?>
SYMFONY     = $(EXEC) bin/console
<?php endif; ?>
<?php else: ?>
PHP        = php
COMPOSER   = composer
<?php if ($isSymfony): ?>
SYMFONY    = php bin/console
<?php endif; ?>
<?php endif; ?>

# Misc
.DEFAULT_GOAL = help

## —— Help ————————————————————————————————————————————————————————————————
.PHONY: help
help: ## Display this help
	@grep -E '(^[a-zA-Z_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-20s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'

## —— Project —————————————————————————————————————————————————————————————
<?php if ($devEnvironment === 'symfony-cli'): ?>
.PHONY: install run abort

install: ## Install project dependencies
	$(COMPOSER) install
<?php if ($isSymfony): ?>
	$(SYMFONY) doctrine:migrations:migrate --no-interaction --allow-no-migration
<?php endif; ?>

run: ## Start Symfony server
	$(SYMFONY_CLI) server:start -d

abort: ## Stop Symfony server
	$(SYMFONY_CLI) server:stop
<?php elseif ($devEnvironment === 'docker'): ?>
.PHONY: install run abort restart

install: ## Install project dependencies
	$(COMPOSER) install
<?php if ($isSymfony): ?>
	$(SYMFONY) doctrine:migrations:migrate --no-interaction --allow-no-migration
<?php endif; ?>

run: ## Start Docker containers
	$(DOCKER) up -d

abort: ## Stop Docker containers
	$(DOCKER) down

restart: abort run ## Restart Docker containers
<?php else: ?>
.PHONY: install

install: ## Install project dependencies
	$(COMPOSER) install
<?php if ($isSymfony): ?>
	$(SYMFONY) doctrine:migrations:migrate --no-interaction --allow-no-migration
<?php endif; ?>
<?php endif; ?>

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
	$(SYMFONY) doctrine:migrations:migrate --no-interaction --allow-no-migration

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
<?php if ($includePhpCsFixer || $includePhpStan || $includeRector): ?>
.PHONY: tools<?php if ($includePhpCsFixer): ?> csf csf-fix<?php endif; ?><?php if ($includePhpStan): ?> stan<?php endif; ?><?php if ($includeRector): ?> rector rector-fix<?php endif; ?><?php if ($includePhpCsFixer && $includePhpStan): ?> analyze<?php endif; ?><?php if ($includePhpCsFixer): ?> fix<?php endif; ?>

tools: ## Install code quality tools
<?php if ($includePhpCsFixer): ?>
	$(COMPOSER) install --working-dir=tools/php-cs-fixer
<?php endif; ?>
<?php if ($includePhpStan): ?>
	$(COMPOSER) install --working-dir=tools/phpstan
<?php endif; ?>
<?php if ($includeRector): ?>
	$(COMPOSER) install --working-dir=tools/rector
<?php endif; ?>

<?php endif; ?>
<?php if ($includePhpCsFixer): ?>
tools/php-cs-fixer/vendor/bin/php-cs-fixer:
	$(COMPOSER) install --working-dir=tools/php-cs-fixer

csf: tools/php-cs-fixer/vendor/bin/php-cs-fixer ## Check code style (dry-run)
	$(PHP) tools/php-cs-fixer/vendor/bin/php-cs-fixer fix --dry-run --diff

csf-fix: tools/php-cs-fixer/vendor/bin/php-cs-fixer ## Fix code style
	$(PHP) tools/php-cs-fixer/vendor/bin/php-cs-fixer fix

<?php endif; ?>
<?php if ($includePhpStan): ?>
tools/phpstan/vendor/bin/phpstan:
	$(COMPOSER) install --working-dir=tools/phpstan

stan: tools/phpstan/vendor/bin/phpstan ## Run PHPStan
	$(PHP) tools/phpstan/vendor/bin/phpstan analyse

<?php endif; ?>
<?php if ($includeRector): ?>
tools/rector/vendor/bin/rector:
	$(COMPOSER) install --working-dir=tools/rector

rector: tools/rector/vendor/bin/rector ## Run Rector (dry-run)
	$(PHP) tools/rector/vendor/bin/rector process --dry-run

rector-fix: tools/rector/vendor/bin/rector ## Run Rector and apply changes
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
<?php if ($devEnvironment === 'symfony-cli'): ?>
	$(SYMFONY_CLI) php bin/phpunit
<?php elseif ($devEnvironment === 'docker'): ?>
	$(EXEC) bin/phpunit
<?php else: ?>
	$(PHP) bin/phpunit
<?php endif; ?>

test-coverage: ## Run tests with coverage report
<?php if ($devEnvironment === 'symfony-cli'): ?>
	$(SYMFONY_CLI) php bin/phpunit --coverage-html var/coverage --coverage-text
<?php elseif ($devEnvironment === 'docker'): ?>
	$(EXEC) bin/phpunit --coverage-html var/coverage --coverage-text
<?php else: ?>
	$(PHP) bin/phpunit --coverage-html var/coverage --coverage-text
<?php endif; ?>

## —— CI ——————————————————————————————————————————————————————————————————
<?php
$ciDeps = [];
if ($isSymfony) {
    $ciDeps[] = 'lint';
}
if ($includePhpCsFixer) {
    $ciDeps[] = 'csf';
}
if ($includePhpStan) {
    $ciDeps[] = 'stan';
}
$ciDeps[] = 'test';

$qaDeps = [];
if ($includePhpCsFixer) {
    $qaDeps[] = 'csf';
}
if ($includePhpStan) {
    $qaDeps[] = 'stan';
}
$qaDeps[] = 'test';
?>
.PHONY: ci qa

ci: <?= implode(' ', $ciDeps) ?> ## Run full CI pipeline

qa: <?= implode(' ', $qaDeps) ?> ## Run QA checks
