FROM php:<?= $phpVersion ?>-cli

RUN apt-get update && apt-get install -y \
        git \
        unzip \
        libicu-dev \
        libzip-dev \
<?php if ($hasDatabase && $databaseType === 'postgresql'): ?>
        libpq-dev \
<?php endif; ?>
    && docker-php-ext-install \
        intl \
        zip \
<?php if ($hasDatabase): ?>
        pdo \
        <?= $phpDatabaseExtension === 'pgsql' ? 'pdo_pgsql' : 'pdo_mysql' ?> \
<?php endif; ?>
    && pecl install pcov \
    && docker-php-ext-enable pcov \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
