name: <?= $projectName ?>

<?php if ($isSymfony): ?>
type: symfony
docroot: public
<?php else: ?>
type: php
<?php endif; ?>
php_version: "<?= $phpVersion ?>"
webserver_type: nginx-fpm
xdebug_enabled: false
additional_hostnames: []
additional_fqdns: []
database:
    type: mariadb
    version: "10.11"
use_dns_when_possible: true
composer_version: "2"
web_environment: []
corepack_enable: false

hooks:
    post-start:
        - exec-host: ddev mysql -e "CREATE DATABASE IF NOT EXISTS db_test; GRANT ALL PRIVILEGES ON db_test.* TO 'db'@'%';"
