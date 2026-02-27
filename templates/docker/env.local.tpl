###> enabel/coding-standard ###
<?php if ($devEnvironment === 'docker'): ?>
DATABASE_URL="<?= $databaseUrlDocker ?>"
MAILER_DSN="smtp://mailer:1025"
REDIS_URL="redis://redis:6379"
<?php endif; ?>
###< enabel/coding-standard ###
