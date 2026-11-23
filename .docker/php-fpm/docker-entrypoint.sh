#!/bin/sh
set -e

# only prepare the app for php-fpm / bin/console, not for e.g. `docker run ... sh`
if [ "$1" = 'php-fpm' ] || [ "$1" = 'php' ] || [ "$1" = 'bin/console' ]; then
    # the cache is warmed at start, not at image build: the compiled container embeds the real
    # environment of this container (APP_SECRET, DATABASE_URL, ...)
    rm -rf var/cache/prod
    php bin/console cache:warmup --no-interaction

    if [ "${RUN_MIGRATIONS:-0}" = '1' ]; then
        php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
    fi
fi

exec "$@"
