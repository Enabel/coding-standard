{
    "require": {
        "ekino/phpstan-banned-code": "^3.0",
        "phpstan/extension-installer": "^1.4",
        "phpstan/phpstan": "^2",
<?php if ($isSymfony): ?>
        "phpstan/phpstan-phpunit": "^2.0",
        "phpstan/phpstan-symfony": "^2.0"
<?php else: ?>
        "phpstan/phpstan-phpunit": "^2.0"
<?php endif; ?>
    },
    "config": {
        "allow-plugins": {
            "phpstan/extension-installer": true
        }
    }
}
