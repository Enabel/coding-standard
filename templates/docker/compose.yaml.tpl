services:
<?php if ($devEnvironment === 'docker'): ?>
  php:
    build:
      context: .
      args:
        PHP_VERSION: "<?= $phpVersion ?>"
    volumes:
      - ./:/app
    environment:
      SERVER_NAME: ":80"
<?php if ($isSymfony): ?>
      SYMFONY_DOTENV_PATH: /app/.env
<?php endif; ?>

<?php endif; ?>
<?php if ($databaseType !== null): ?>
  database:
    image: <?= $databaseImage ?>

<?php if ($databaseType === 'postgresql'): ?>
    volumes:
      - database_data:/var/lib/postgresql/data
<?php else: ?>
    volumes:
      - database_data:/var/lib/mysql
<?php endif; ?>
    environment:
<?php foreach ($databaseEnvVars as $key => $value): ?>
      <?= $key ?>: "<?= $value ?>"
<?php endforeach; ?>
    healthcheck:
<?php if ($databaseType === 'postgresql'): ?>
      test: ["CMD-SHELL", "pg_isready -U $$POSTGRES_USER"]
<?php else: ?>
      test: ["CMD", "healthcheck.sh", "--connect", "--innodb_initialized"]
<?php endif; ?>
      interval: 10s
      timeout: 5s
      retries: 5
      start_period: 30s

<?php endif; ?>
  mailer:
    image: axllent/mailpit

  redis:
    image: redis:alpine
    healthcheck:
      test: ["CMD", "redis-cli", "ping"]
      interval: 10s
      timeout: 5s
      retries: 5

<?php if ($databaseType !== null): ?>
volumes:
  database_data:
<?php endif; ?>
