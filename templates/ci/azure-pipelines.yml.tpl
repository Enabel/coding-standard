trigger:
  branches:
    include:
      - main
      - master

pr:
  branches:
    include:
      - main
      - master

pool:
  vmImage: 'ubuntu-latest'

variables:
  COMPOSER_ALLOW_SUPERUSER: 1
  COMPOSER_NO_INTERACTION: 1
  COMPOSER_CACHE_DIR: $(Pipeline.Workspace)/.composer
  PHP_VERSION: '<?= $phpVersion ?>'

stages:
  - stage: Build
    displayName: 'Build'
    jobs:
      - job: Build
        displayName: 'Install dependencies'
        steps:
          - task: Cache@2
            inputs:
              key: 'composer | "$(Agent.OS)" | composer.lock'
              restoreKeys: |
                composer | "$(Agent.OS)"
              path: $(COMPOSER_CACHE_DIR)
            displayName: 'Cache Composer packages'

          - script: |
              sudo add-apt-repository ppa:ondrej/php -y
              sudo apt-get update
              sudo apt-get install -y php$(PHP_VERSION)-cli php$(PHP_VERSION)-intl php$(PHP_VERSION)-zip<?php if ($hasDatabase): ?> php$(PHP_VERSION)-<?= $phpDatabaseExtension ?><?php endif; ?> php$(PHP_VERSION)-xml php$(PHP_VERSION)-mbstring
              sudo update-alternatives --set php /usr/bin/php$(PHP_VERSION)
            displayName: 'Setup PHP $(PHP_VERSION)'

          - script: composer validate --no-check-publish
            displayName: 'Validate composer.json'

          - script: composer install --prefer-dist --no-progress
            displayName: 'Install dependencies'

<?php if ($includePhpCsFixer): ?>
          - script: composer install --prefer-dist --no-progress -d tools/php-cs-fixer
            displayName: 'Install PHP-CS-Fixer'

<?php endif; ?>
<?php if ($includePhpStan): ?>
          - script: composer install --prefer-dist --no-progress -d tools/phpstan
            displayName: 'Install PHPStan'

<?php endif; ?>
<?php if ($includeRector): ?>
          - script: composer install --prefer-dist --no-progress -d tools/rector
            displayName: 'Install Rector'

<?php endif; ?>
<?php if ($isSymfony): ?>
  - stage: Lint
    displayName: 'Lint'
    dependsOn: Build
    jobs:
      - job: Lint
        displayName: 'Lint files'
        steps:
          - task: Cache@2
            inputs:
              key: 'composer | "$(Agent.OS)" | composer.lock'
              restoreKeys: |
                composer | "$(Agent.OS)"
              path: $(COMPOSER_CACHE_DIR)
            displayName: 'Cache Composer packages'

          - script: |
              sudo add-apt-repository ppa:ondrej/php -y
              sudo apt-get update
              sudo apt-get install -y php$(PHP_VERSION)-cli php$(PHP_VERSION)-intl php$(PHP_VERSION)-zip php$(PHP_VERSION)-xml php$(PHP_VERSION)-mbstring
              sudo update-alternatives --set php /usr/bin/php$(PHP_VERSION)
            displayName: 'Setup PHP $(PHP_VERSION)'

          - script: composer install --prefer-dist --no-progress
            displayName: 'Install dependencies'

          - script: bin/console lint:yaml config --parse-tags
            displayName: 'Lint YAML'

          - script: bin/console lint:twig templates
            displayName: 'Lint Twig'

          - script: bin/console lint:container
            displayName: 'Lint Container'

          - script: composer validate --no-check-publish
            displayName: 'Validate composer.json'

<?php endif; ?>
  - stage: Analyze
    displayName: 'Analyze'
    dependsOn: Build
    jobs:
      - job: Analyze
        displayName: 'Static analysis'
        steps:
          - task: Cache@2
            inputs:
              key: 'composer | "$(Agent.OS)" | composer.lock'
              restoreKeys: |
                composer | "$(Agent.OS)"
              path: $(COMPOSER_CACHE_DIR)
            displayName: 'Cache Composer packages'

          - script: |
              sudo add-apt-repository ppa:ondrej/php -y
              sudo apt-get update
              sudo apt-get install -y php$(PHP_VERSION)-cli php$(PHP_VERSION)-intl php$(PHP_VERSION)-zip php$(PHP_VERSION)-xml php$(PHP_VERSION)-mbstring
              sudo update-alternatives --set php /usr/bin/php$(PHP_VERSION)
            displayName: 'Setup PHP $(PHP_VERSION)'

          - script: composer install --prefer-dist --no-progress
            displayName: 'Install dependencies'

<?php if ($includePhpCsFixer): ?>
          - script: composer install --prefer-dist --no-progress -d tools/php-cs-fixer
            displayName: 'Install PHP-CS-Fixer'

          - script: tools/php-cs-fixer/vendor/bin/php-cs-fixer fix --dry-run --diff
            displayName: 'Check code style'

<?php endif; ?>
<?php if ($includePhpStan): ?>
          - script: composer install --prefer-dist --no-progress -d tools/phpstan
            displayName: 'Install PHPStan'

          - script: tools/phpstan/vendor/bin/phpstan analyse
            displayName: 'Run PHPStan'

<?php endif; ?>
  - stage: Test
    displayName: 'Test'
    dependsOn: Build
    jobs:
      - job: Test
        displayName: 'Run tests'
<?php if ($hasDatabase): ?>
        services:
          database:
            image: <?= $databaseImage ?>

            env:
<?php foreach ($databaseEnvVars as $key => $value): ?>
              <?= $key ?>: <?= $value ?>

<?php endforeach; ?>
            ports:
              - <?= $databasePort ?>:<?= $databasePort ?>

<?php endif; ?>
        steps:
          - task: Cache@2
            inputs:
              key: 'composer | "$(Agent.OS)" | composer.lock'
              restoreKeys: |
                composer | "$(Agent.OS)"
              path: $(COMPOSER_CACHE_DIR)
            displayName: 'Cache Composer packages'

          - script: |
              sudo add-apt-repository ppa:ondrej/php -y
              sudo apt-get update
              sudo apt-get install -y php$(PHP_VERSION)-cli php$(PHP_VERSION)-intl php$(PHP_VERSION)-zip<?php if ($hasDatabase): ?> php$(PHP_VERSION)-<?= $phpDatabaseExtension ?><?php endif; ?> php$(PHP_VERSION)-xml php$(PHP_VERSION)-mbstring
              sudo update-alternatives --set php /usr/bin/php$(PHP_VERSION)
            displayName: 'Setup PHP $(PHP_VERSION)'

          - script: composer install --prefer-dist --no-progress
            displayName: 'Install dependencies'

<?php if ($isSymfony): ?>
          - script: bin/console importmap:install --env=test
            displayName: 'Install importmap assets'

<?php endif; ?>
<?php if ($hasDatabase): ?>
          - script: bin/console doctrine:database:create --if-not-exists --env=test
            displayName: 'Create database'
            env:
              DATABASE_URL: <?= $databaseUrl ?>

          - script: bin/console doctrine:migrations:migrate --no-interaction --env=test
            displayName: 'Run migrations'
            env:
              DATABASE_URL: <?= $databaseUrl ?>

<?php endif; ?>
          - script: bin/phpunit --testdox
            displayName: 'Run PHPUnit'
<?php if ($hasDatabase): ?>
            env:
              DATABASE_URL: <?= $databaseUrl ?>
<?php endif; ?>

          - task: PublishTestResults@2
            condition: succeededOrFailed()
            inputs:
              testResultsFormat: 'JUnit'
              testResultsFiles: '**/junit.xml'
              failTaskOnFailedTests: true
            displayName: 'Publish test results'
