# Executables
PHP        = php
PHPUNIT	   = vendor/bin/phpunit
COMPOSER   = composer

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
.PHONY: csf csf-fix stan rector rector-fix analyze fix

csf: ## Check code style (dry-run)
	$(PHP) tools/php-cs-fixer/vendor/bin/php-cs-fixer fix --dry-run --diff

csf-fix: ## Fix code style
	$(PHP) tools/php-cs-fixer/vendor/bin/php-cs-fixer fix

stan: ## Run PHPStan
	$(PHP) tools/phpstan/vendor/bin/phpstan analyse

rector: ## Run Rector (dry-run)
	$(PHP) tools/rector/vendor/bin/rector process --dry-run

rector-fix: ## Run Rector and apply changes
	$(PHP) tools/rector/vendor/bin/rector process

analyze: csf stan ## Run code style check and PHPStan

fix: csf-fix ## Fix code style issues

## —— Tests ———————————————————————————————————————————————————————————————
.PHONY: test test-coverage

test: ## Run PHPUnit tests
	$(PHPUNIT)

test-coverage: ## Run tests with coverage report
	$(PHPUNIT) --coverage-html var/coverage --coverage-text

## —— CI ——————————————————————————————————————————————————————————————————
.PHONY: ci qa

ci: analyze test ## Run full CI pipeline (analyze + test)

qa: csf stan test ## Run QA checks
