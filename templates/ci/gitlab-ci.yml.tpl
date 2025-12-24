stages:
  - build
  - lint
  - analyze
  - test

variables:
  COMPOSER_ALLOW_SUPERUSER: 1
  COMPOSER_NO_INTERACTION: 1

.php-image: &php-image
  image: php:<?= $phpVersion ?>-cli

.composer-cache: &composer-cache
  cache:
    key: composer-$CI_COMMIT_REF_SLUG
    paths:
      - vendor/
<?php if ($includePhpCsFixer): ?>
      - tools/php-cs-fixer/vendor/
<?php endif; ?>
<?php if ($includePhpStan): ?>
      - tools/phpstan/vendor/
<?php endif; ?>
<?php if ($includeRector): ?>
      - tools/rector/vendor/
<?php endif; ?>
    policy: pull

# ====================
# Build Stage
# ====================

build:
  <<: *php-image
  stage: build
  before_script:
    - apt-get update && apt-get install -y git unzip libicu-dev libzip-dev<?php if ($hasDatabase && $databaseType === 'postgresql'): ?> libpq-dev<?php endif; ?>

    - docker-php-ext-install intl zip<?php if ($hasDatabase): ?> pdo <?= $databaseType === 'postgresql' ? 'pdo_pgsql' : 'pdo_mysql' ?><?php endif; ?>

    - curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
  script:
    - composer validate --no-check-publish
    - composer install --prefer-dist --no-progress
<?php if ($includePhpCsFixer): ?>
    - composer install --prefer-dist --no-progress -d tools/php-cs-fixer
<?php endif; ?>
<?php if ($includePhpStan): ?>
    - composer install --prefer-dist --no-progress -d tools/phpstan
<?php endif; ?>
<?php if ($includeRector): ?>
    - composer install --prefer-dist --no-progress -d tools/rector
<?php endif; ?>
  cache:
    key: composer-$CI_COMMIT_REF_SLUG
    paths:
      - vendor/
<?php if ($includePhpCsFixer): ?>
      - tools/php-cs-fixer/vendor/
<?php endif; ?>
<?php if ($includePhpStan): ?>
      - tools/phpstan/vendor/
<?php endif; ?>
<?php if ($includeRector): ?>
      - tools/rector/vendor/
<?php endif; ?>
    policy: pull-push

<?php if ($isSymfony): ?>
# ====================
# Lint Stage
# ====================

lint:yaml:
  <<: *php-image
  <<: *composer-cache
  stage: lint
  needs: [build]
  before_script:
    - apt-get update && apt-get install -y libicu-dev libzip-dev
    - docker-php-ext-install intl zip
  script:
    - bin/console lint:yaml config --parse-tags

lint:twig:
  <<: *php-image
  <<: *composer-cache
  stage: lint
  needs: [build]
  before_script:
    - apt-get update && apt-get install -y libicu-dev libzip-dev
    - docker-php-ext-install intl zip
  script:
    - bin/console lint:twig templates

lint:container:
  <<: *php-image
  <<: *composer-cache
  stage: lint
  needs: [build]
  before_script:
    - apt-get update && apt-get install -y libicu-dev libzip-dev
    - docker-php-ext-install intl zip
  script:
    - bin/console lint:container

lint:composer:
  <<: *php-image
  <<: *composer-cache
  stage: lint
  needs: [build]
  before_script:
    - apt-get update && apt-get install -y git unzip
    - curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
  script:
    - composer validate --no-check-publish

<?php endif; ?>
# ====================
# Analyze Stage
# ====================

<?php if ($includePhpCsFixer): ?>
php-cs-fixer:
  <<: *php-image
  <<: *composer-cache
  stage: analyze
  needs: [build]
  script:
    - tools/php-cs-fixer/vendor/bin/php-cs-fixer fix --dry-run --diff

<?php endif; ?>
<?php if ($includePhpStan): ?>
phpstan:
  <<: *php-image
  <<: *composer-cache
  stage: analyze
  needs: [build]
  before_script:
    - apt-get update && apt-get install -y libicu-dev libzip-dev
    - docker-php-ext-install intl zip
  script:
    - tools/phpstan/vendor/bin/phpstan analyse

<?php endif; ?>
# ====================
# Test Stage
# ====================

phpunit:
  <<: *php-image
  stage: test
  needs: [build]
<?php if ($hasDatabase): ?>
  services:
    - name: <?= $databaseImage ?>

      alias: database
      variables:
<?php foreach ($databaseEnvVars as $key => $value): ?>
        <?= $key ?>: <?= $value ?>

<?php endforeach; ?>
  variables:
    APP_ENV: test
    DATABASE_URL: "<?= str_replace('127.0.0.1', 'database', $databaseUrl) ?>"
<?php endif; ?>
  before_script:
    - apt-get update && apt-get install -y git unzip libicu-dev libzip-dev<?php if ($hasDatabase && $databaseType === 'postgresql'): ?> libpq-dev<?php endif; ?>

    - docker-php-ext-install intl zip<?php if ($hasDatabase): ?> pdo <?= $databaseType === 'postgresql' ? 'pdo_pgsql' : 'pdo_mysql' ?><?php endif; ?>

<?php if ($hasDatabase): ?>
    # Wait for database to be ready
    - |
      for i in $(seq 1 30); do
<?php if ($databaseType === 'postgresql'): ?>
        if php -r "try { new PDO('pgsql:host=database;port=<?= $databasePort ?>', 'db', 'db'); echo 'OK'; exit(0); } catch(Exception \$e) { exit(1); }"; then
          echo "PostgreSQL is ready!"
<?php else: ?>
        if php -r "try { new PDO('mysql:host=database;port=<?= $databasePort ?>', 'db', 'db'); echo 'OK'; exit(0); } catch(Exception \$e) { exit(1); }"; then
          echo "Database is ready!"
<?php endif; ?>
          break
        fi
        echo "Waiting for database... ($i/30)"
        sleep 2
      done
<?php endif; ?>
  script:
<?php if ($isSymfony): ?>
    - bin/console importmap:install --env=test
<?php endif; ?>
<?php if ($hasDatabase): ?>
    - bin/console doctrine:database:create --if-not-exists --env=test
    - bin/console doctrine:migrations:migrate --no-interaction --env=test
<?php endif; ?>
    - bin/phpunit --testdox
  cache:
    key: composer-$CI_COMMIT_REF_SLUG
    paths:
      - vendor/
    policy: pull
