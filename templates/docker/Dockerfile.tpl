ARG PHP_VERSION
FROM dunglas/frankenphp:1-php${PHP_VERSION}-alpine

RUN cp $PHP_INI_DIR/php.ini-development $PHP_INI_DIR/php.ini

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

RUN install-php-extensions bcmath intl opcache pcov pdo_<?= $phpDatabaseExtension ?> redis

WORKDIR /app
