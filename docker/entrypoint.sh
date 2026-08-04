#!/bin/sh
set -e

if [ ! -f .env ]; then
    cp .env.example .env
fi

if ! grep -q '^APP_KEY=base64:' .env; then
    php artisan key:generate --force
fi

if [ ! -f database/database.sqlite ]; then
    touch database/database.sqlite
fi

if [ -n "$ELASTICSEARCH_HOST" ]; then
    if grep -q '^ELASTICSEARCH_HOST=' .env; then
        sed -i "s|^ELASTICSEARCH_HOST=.*|ELASTICSEARCH_HOST=${ELASTICSEARCH_HOST}|" .env
    else
        printf '\nELASTICSEARCH_HOST=%s\n' "$ELASTICSEARCH_HOST" >> .env
    fi
fi

php artisan migrate --force

chmod -R 775 storage bootstrap/cache

exec "$@"
