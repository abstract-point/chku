FROM php:8.5.5-fpm-trixie

WORKDIR /var/www/backend

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        libicu-dev \
        libpq-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-install intl pdo_pgsql pgsql zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2.9 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts --no-autoloader

COPY . .

RUN composer dump-autoload --optimize \
    && chown -R www-data:www-data storage bootstrap/cache

CMD ["php-fpm"]
