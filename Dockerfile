# syntax=docker/dockerfile:1

FROM php:8.4-cli-alpine

# Build-time deps for compiling PHP extensions; remove afterwards, keep runtime libs.
RUN apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        icu-dev \
        libzip-dev \
        libpng-dev \
        sqlite-dev \
    && docker-php-ext-install \
        intl \
        pdo_sqlite \
        pdo_mysql \
        zip \
        gd \
        bcmath \
    && apk del .build-deps \
    && apk add --no-cache icu-libs libzip libpng sqlite-libs git unzip
# pdo_mysql is baked in even when DB_CONNECTION=sqlite so flipping to
# MySQL is a config change only — no Docker rebuild required.

COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-interaction \
        --no-progress \
        --no-scripts \
        --prefer-dist \
        --optimize-autoloader

COPY . .

RUN composer dump-autoload --optimize \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs database \
    && chmod -R 777 storage bootstrap/cache database

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 8000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
