# syntax=docker/dockerfile:1

FROM composer:2 AS composer

# Wspólna baza: PHP-FPM z rozszerzeniami potrzebnymi aplikacji i zadaniom CRON.
FROM php:8.4-fpm-alpine AS base

# Pakiety -dev są potrzebne tylko do kompilacji rozszerzeń, więc usuwamy je w tej samej warstwie.
# supercronic zamiast busybox crond, bo przekazuje zmienne środowiskowe do uruchamianych zadań.
RUN apk add --no-cache libpq supercronic tzdata \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS postgresql-dev \
    && docker-php-ext-install pdo_pgsql opcache \
    && apk del .build-deps

COPY --from=composer /usr/bin/composer /usr/bin/composer
# Prefiksy liczbowe ustalają kolejność ładowania: nadpisania dev (zz-20) muszą wczytać się po bazie (zz-10).
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-10-app.ini
COPY docker/php/entrypoint.sh /usr/local/bin/app-entrypoint
RUN chmod +x /usr/local/bin/app-entrypoint

WORKDIR /var/www/html

ENTRYPOINT ["app-entrypoint"]
CMD ["php-fpm"]

# Środowisko developerskie: kod montowany jako bind mount, błędy widoczne, opcache odświeża pliki.
FROM base AS dev
COPY docker/php/php.dev.ini /usr/local/etc/php/conf.d/zz-20-app-dev.ini

# Produkcja: kod i zależności zapieczone w obrazie, bez pakietów developerskich.
FROM base AS prod
COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction
COPY . .
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative \
    && chown -R www-data:www-data storage
