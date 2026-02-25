###> enabel/coding-standard ###
<?php if ($devEnvironment === 'docker'): ?>
DATABASE_URL="<?= $databaseUrlDocker ?>"
MAILER_DSN="smtp://mailer:1025"
REDIS_URL="redis://redis:6379"
<?php else: ?>
DATABASE_URL="<?= $databaseUrl ?>"
MAILER_DSN="smtp://127.0.0.1:1025"
REDIS_URL="redis://127.0.0.1:6379"
<?php endif; ?>
###< enabel/coding-standard ###
