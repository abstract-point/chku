FROM php:8.5.5-fpm-trixie

WORKDIR /var/www/backend

ARG APP_UID=1000
ARG APP_GID=1000

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        libicu-dev \
        libpq-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-install intl pdo_pgsql pgsql zip \
    && rm -rf /var/lib/apt/lists/*

RUN groupadd --gid ${APP_GID} app \
    && useradd --uid ${APP_UID} --gid app --shell /bin/sh --create-home app \
    && sed -i 's/user = www-data/user = app/' /usr/local/etc/php-fpm.d/www.conf \
    && sed -i 's/group = www-data/group = app/' /usr/local/etc/php-fpm.d/www.conf

COPY --from=composer:2.9 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-scripts --optimize-autoloader

CMD ["php-fpm"]
