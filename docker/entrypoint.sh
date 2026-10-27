#!/bin/sh
set -e

cd /var/www/html

mkdir -p storage/app storage/framework/cache storage/framework/sessions storage/framework/views storage/logs

if [ -z "$APP_KEY" ]; then
    export APP_KEY="base64:$(openssl rand -base64 32)"
fi

echo "Waiting for the database..."
attempts=0
until php artisan migrate --force --no-interaction; do
    attempts=$((attempts + 1))
    if [ "$attempts" -ge 30 ]; then
        echo "Database is not reachable, giving up." >&2
        exit 1
    fi
    sleep 2
done

php artisan api:setup --no-interaction

if [ "${SEED_DEMO_USER:-true}" = "true" ]; then
    php artisan db:seed --force --no-interaction
fi

chown -R www-data:www-data storage bootstrap/cache

exec "$@"
