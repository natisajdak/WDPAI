#!/bin/sh
set -e

cd /var/www/html

# W dev kod jest montowany z hosta, więc vendor/ z obrazu jest zasłonięty.
# Zależności instaluje tylko kontener "app", żeby "app" i "cron" nie robiły tego naraz.
if [ "${RUN_COMPOSER_INSTALL:-0}" = "1" ] && [ -f composer.json ] && [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --prefer-dist
fi

mkdir -p storage/uploads/faults storage/logs
chown -R www-data:www-data storage

# Tak jak oryginalny entrypoint obrazu PHP: same flagi (np. "-F") trafiają do php-fpm.
if [ "${1#-}" != "$1" ]; then
    set -- php-fpm "$@"
fi

exec "$@"
