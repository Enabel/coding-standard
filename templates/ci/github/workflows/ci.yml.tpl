name: CI

on:
  push:
    branches: [main, master]
  pull_request:
    branches: [main, master]

permissions:
  contents: read
  packages: write

jobs:
  build-image:
    runs-on: ubuntu-latest
    outputs:
      image: ${{ steps.meta.outputs.image }}
    steps:
      - name: Set image name
        id: meta
        run: echo "image=ghcr.io/${GITHUB_REPOSITORY,,}/ci:php<?= $phpVersion ?>" >> $GITHUB_OUTPUT

      - uses: actions/checkout@v4

      - name: Log in to GHCR
        uses: docker/login-action@v3
        with:
          registry: ghcr.io
          username: ${{ github.actor }}
          password: ${{ secrets.GITHUB_TOKEN }}

      - name: Set up Docker Buildx
        uses: docker/setup-buildx-action@v3

      - name: Build and push CI image
        uses: docker/build-push-action@v6
        with:
          context: .
          file: .github/ci/Dockerfile
          push: true
          tags: ${{ steps.meta.outputs.image }}
          cache-from: type=gha
          cache-to: type=gha,mode=max

  build:
    needs: [build-image]
    runs-on: ubuntu-latest
    container:
      image: ${{ needs.build-image.outputs.image }}
      credentials:
        username: ${{ github.actor }}
        password: ${{ secrets.GITHUB_TOKEN }}
    steps:
      - uses: actions/checkout@v4

      - name: Get Composer cache directory
        id: composer-cache
        run: echo "dir=$(composer config cache-files-dir)" >> $GITHUB_OUTPUT

      - name: Cache Composer dependencies
        uses: actions/cache@v4
        with:
          path: ${{ steps.composer-cache.outputs.dir }}
          key: ${{ runner.os }}-composer-${{ hashFiles('**/composer.lock') }}
          restore-keys: ${{ runner.os }}-composer-

      - name: Validate composer.json
        run: composer validate --no-check-publish

      - name: Install dependencies
        run: composer install --prefer-dist --no-progress

<?php if ($includePhpCsFixer): ?>
      - name: Install PHP-CS-Fixer
        run: composer install --prefer-dist --no-progress -d tools/php-cs-fixer

<?php endif; ?>
<?php if ($includePhpStan): ?>
      - name: Install PHPStan
        run: composer install --prefer-dist --no-progress -d tools/phpstan

<?php endif; ?>
<?php if ($includeRector): ?>
      - name: Install Rector
        run: composer install --prefer-dist --no-progress -d tools/rector

<?php endif; ?>
      - name: Cache tools
        uses: actions/cache@v4
        with:
          path: |
            tools/*/vendor
          key: ${{ runner.os }}-tools-${{ hashFiles('tools/*/composer.lock') }}

<?php if ($isSymfony): ?>
  lint:
    needs: [build-image, build]
    runs-on: ubuntu-latest
    container:
      image: ${{ needs.build-image.outputs.image }}
      credentials:
        username: ${{ github.actor }}
        password: ${{ secrets.GITHUB_TOKEN }}
    steps:
      - uses: actions/checkout@v4

      - name: Install dependencies
        run: composer install --prefer-dist --no-progress

      - name: Lint YAML
        run: bin/console lint:yaml config --parse-tags

      - name: Lint Twig
        run: bin/console lint:twig templates

      - name: Lint Container
        run: bin/console lint:container

      - name: Validate composer.json
        run: composer validate --no-check-publish

<?php endif; ?>
  analyze:
    needs: [build-image, build]
    runs-on: ubuntu-latest
    container:
      image: ${{ needs.build-image.outputs.image }}
      credentials:
        username: ${{ github.actor }}
        password: ${{ secrets.GITHUB_TOKEN }}
    steps:
      - uses: actions/checkout@v4

      - name: Install dependencies
        run: composer install --prefer-dist --no-progress

<?php if ($includePhpCsFixer): ?>
      - name: Install PHP-CS-Fixer
        run: composer install --prefer-dist --no-progress -d tools/php-cs-fixer

      - name: Check code style
        run: tools/php-cs-fixer/vendor/bin/php-cs-fixer fix --dry-run --diff

<?php endif; ?>
<?php if ($includePhpStan): ?>
      - name: Install PHPStan
        run: composer install --prefer-dist --no-progress -d tools/phpstan

      - name: Run PHPStan
        run: tools/phpstan/vendor/bin/phpstan analyse

<?php endif; ?>
  test:
    needs: [build-image, build]
    runs-on: ubuntu-latest
<?php if ($hasDatabase): ?>
    services:
      database:
        image: <?= $databaseImage ?>

        env:
<?php foreach ($databaseEnvVars as $key => $value): ?>
          <?= $key ?>: <?= $value ?>

<?php endforeach; ?>
<?php if ($databaseType === 'postgresql'): ?>
        options: --health-cmd pg_isready --health-interval=10s --health-timeout=5s --health-retries=3
<?php else: ?>
        options: --health-cmd="mysqladmin ping -h 127.0.0.1 -u root --silent" --health-interval=5s --health-timeout=5s --health-retries=10
<?php endif; ?>
<?php endif; ?>
    container:
      image: ${{ needs.build-image.outputs.image }}
      credentials:
        username: ${{ github.actor }}
        password: ${{ secrets.GITHUB_TOKEN }}
    steps:
      - uses: actions/checkout@v4

      - name: Install dependencies
        run: composer install --prefer-dist --no-progress

<?php if ($isSymfony): ?>
      - name: Install importmap assets
        run: bin/console importmap:install --env=test

<?php endif; ?>
<?php if ($hasDatabase): ?>
      - name: Create database
        run: bin/console doctrine:database:create --if-not-exists --env=test
        env:
          DATABASE_URL: "<?= str_replace('127.0.0.1', 'database', $databaseUrl) ?>"

      - name: Run migrations
        run: bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration --env=test
        env:
          DATABASE_URL: "<?= str_replace('127.0.0.1', 'database', $databaseUrl) ?>"

<?php endif; ?>
      - name: Run tests
        run: bin/phpunit --testdox
<?php if ($hasDatabase): ?>
        env:
          DATABASE_URL: "<?= str_replace('127.0.0.1', 'database', $databaseUrl) ?>"
<?php endif; ?>
