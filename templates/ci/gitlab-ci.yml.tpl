stages:
  - .pre
  - build
  - lint
  - analyze
  - test
  - security

include:
  - template: Security/SAST.gitlab-ci.yml
  - template: Security/Secret-Detection.gitlab-ci.yml

variables:
  COMPOSER_ALLOW_SUPERUSER: 1
  COMPOSER_NO_INTERACTION: 1
  SECRET_DETECTION_ENABLED: 'true'
  CI_IMAGE: $CI_REGISTRY_IMAGE/ci:php<?= $phpVersion ?>


.ci-image: &ci-image
  image: $CI_IMAGE

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
# Pre Stage — Build CI Image
# ====================

build:image:
  stage: .pre
  image: docker:27
  services:
    - docker:27-dind
  variables:
    DOCKER_TLS_CERTDIR: "/certs"
  script:
    - docker login -u gitlab-ci-token -p $CI_JOB_TOKEN $CI_REGISTRY
    - docker build -t $CI_IMAGE -f .gitlab/ci/Dockerfile .
    - docker push $CI_IMAGE
  rules:
    - changes:
        - .gitlab/ci/Dockerfile
    - if: $BUILD_CI_IMAGE == "true"

# ====================
# Build Stage
# ====================

build:
  <<: *ci-image
  stage: build
  needs:
    - job: build:image
      optional: true
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
  <<: *ci-image
  <<: *composer-cache
  stage: lint
  needs:
    - job: build:image
      optional: true
    - build
  script:
    - bin/console lint:yaml config --parse-tags

lint:twig:
  <<: *ci-image
  <<: *composer-cache
  stage: lint
  needs:
    - job: build:image
      optional: true
    - build
  script:
    - bin/console lint:twig templates

lint:container:
  <<: *ci-image
  <<: *composer-cache
  stage: lint
  needs:
    - job: build:image
      optional: true
    - build
  script:
    - bin/console lint:container

lint:composer:
  <<: *ci-image
  <<: *composer-cache
  stage: lint
  needs:
    - job: build:image
      optional: true
    - build
  script:
    - composer validate --no-check-publish

<?php endif; ?>
# ====================
# Analyze Stage
# ====================

<?php if ($includePhpCsFixer): ?>
php-cs-fixer:
  <<: *ci-image
  <<: *composer-cache
  stage: analyze
  needs:
    - job: build:image
      optional: true
    - build
  script:
    - tools/php-cs-fixer/vendor/bin/php-cs-fixer fix --dry-run --diff

<?php endif; ?>
<?php if ($includePhpStan): ?>
phpstan:
  <<: *ci-image
  <<: *composer-cache
  stage: analyze
  needs:
    - job: build:image
      optional: true
    - build
  script:
<?php if ($isSymfony): ?>
    - bin/console cache:warmup
<?php endif; ?>
    - tools/phpstan/vendor/bin/phpstan analyse

<?php endif; ?>
# ====================
# Test Stage
# ====================

phpunit:
  <<: *ci-image
  stage: test
  needs:
    - job: build:image
      optional: true
    - build
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
  before_script:
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
    - bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration --env=test
<?php endif; ?>
    - bin/phpunit --testdox
  cache:
    key: composer-$CI_COMMIT_REF_SLUG
    paths:
      - vendor/
    policy: pull

# ====================
# Security Stage
# ====================

sast:
  stage: security

secret_detection:
  stage: security
