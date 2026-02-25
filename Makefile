# Executables
SYMFONY_CLI = symfony
PHP         = $(SYMFONY_CLI) php
COMPOSER    = $(SYMFONY_CLI) composer

# Misc
.DEFAULT_GOAL = help

## —— Help ————————————————————————————————————————————————————————————————
.PHONY: help
help: ## Display this help
	@grep -E '(^[a-zA-Z_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-20s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'

## —— Project —————————————————————————————————————————————————————————————
.PHONY: install

install: ## Install project dependencies
	$(COMPOSER) install

## —— Composer ————————————————————————————————————————————————————————————
.PHONY: composer-install composer-update composer-validate

composer-install: ## Install Composer dependencies
	$(COMPOSER) install

composer-update: ## Update Composer dependencies
	$(COMPOSER) update

composer-validate: ## Validate composer.json
	$(COMPOSER) validate --strict

## —— Code Quality ————————————————————————————————————————————————————————
.PHONY: tools csf csf-fix stan rector rector-fix analyze fix
tools: ## Install code quality tools
	$(COMPOSER) install --working-dir=tools/php-cs-fixer
	$(COMPOSER) install --working-dir=tools/phpstan
	$(COMPOSER) install --working-dir=tools/rector

tools/php-cs-fixer/vendor/bin/php-cs-fixer:
	$(COMPOSER) install --working-dir=tools/php-cs-fixer

csf: tools/php-cs-fixer/vendor/bin/php-cs-fixer ## Check code style (dry-run)
	$(PHP) tools/php-cs-fixer/vendor/bin/php-cs-fixer fix --dry-run --diff

csf-fix: tools/php-cs-fixer/vendor/bin/php-cs-fixer ## Fix code style
	$(PHP) tools/php-cs-fixer/vendor/bin/php-cs-fixer fix

tools/phpstan/vendor/bin/phpstan:
	$(COMPOSER) install --working-dir=tools/phpstan

stan: tools/phpstan/vendor/bin/phpstan ## Run PHPStan
	$(PHP) tools/phpstan/vendor/bin/phpstan analyse

tools/rector/vendor/bin/rector:
	$(COMPOSER) install --working-dir=tools/rector

rector: tools/rector/vendor/bin/rector ## Run Rector (dry-run)
	$(PHP) tools/rector/vendor/bin/rector process --dry-run

rector-fix: tools/rector/vendor/bin/rector ## Run Rector and apply changes
	$(PHP) tools/rector/vendor/bin/rector process

analyze: csf stan ## Run code style check and PHPStan

fix: csf-fix ## Fix code style issues

## —— Tests ———————————————————————————————————————————————————————————————
.PHONY: test test-coverage

test: ## Run PHPUnit tests
	$(SYMFONY_CLI) php bin/phpunit

test-coverage: ## Run tests with coverage report
	$(SYMFONY_CLI) php bin/phpunit --coverage-html var/coverage --coverage-text

## —— CI ——————————————————————————————————————————————————————————————————
.PHONY: ci qa

ci: csf stan test ## Run full CI pipeline

qa: csf stan test ## Run QA checks
