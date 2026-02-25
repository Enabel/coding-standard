services:
<?php if ($devEnvironment === 'docker'): ?>
  php:
    ports:
      - "80:80"
      - "443:443"
<?php if ($isSymfony): ?>
    environment:
      DATABASE_URL: "<?= $databaseUrlDocker ?>"
      MAILER_DSN: "smtp://mailer:1025"
      REDIS_URL: "redis://redis:6379"
<?php endif; ?>

<?php endif; ?>
<?php if ($databaseType !== null): ?>
  database:
    ports:
      - "<?= $databasePort ?>:<?= $databasePort ?>"

<?php endif; ?>
  mailer:
    ports:
      - "1025:1025"
      - "8025:8025"

  redis:
    ports:
      - "6379:6379"
