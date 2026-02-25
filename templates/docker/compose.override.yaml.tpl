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
###> symfony/mailer ###
  mailer:
    image: axllent/mailpit
    ports:
      - "1025"
      - "8025:8025"
    environment:
      MP_SMTP_AUTH_ACCEPT_ANY: 1
      MP_SMTP_AUTH_ALLOW_INSECURE: 1
###< symfony/mailer ###

  redis:
    ports:
      - "6379:6379"
<?php if ($includeDbAdmin && in_array($databaseType, ['mariadb', 'mysql'], true)): ?>

  phpmyadmin:
    image: phpmyadmin/phpmyadmin
    ports:
      - "80"
    depends_on:
      - database
    environment:
      PMA_HOST: database
      PMA_USER: root
      UPLOAD_LIMIT: 500M
<?php endif; ?>
