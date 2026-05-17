#!/bin/sh
set -e

cd /var/www/html

# Ensure SQLite database file exists
if [ ! -f database/database.sqlite ]; then
    touch database/database.sqlite
fi

# Generate APP_KEY if missing
if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
    if [ ! -f .env ]; then
        cp .env.example .env
    fi
    php artisan key:generate --force
fi

# Public storage symlink
if [ ! -L public/storage ]; then
    php artisan storage:link || true
fi

# Migrate (idempotent) and seed on first boot
php artisan migrate --force --no-interaction
php artisan db:seed --force --no-interaction || true

# Clear caches so config changes take effect on rebuild
php artisan config:clear
php artisan view:clear
php artisan route:clear

exec "$@"
