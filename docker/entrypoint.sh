#!/bin/bash
set -e

# Ensure storage directories exist with correct permissions
mkdir -p /var/www/storage/framework/{sessions,views,cache} /var/www/storage/logs /var/www/storage/app/public/po_files
chmod -R 775 /var/www/storage /var/www/bootstrap/cache || true
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache || true

# Generate key if not present
if [ ! -f /var/www/.env ]; then
    if [ -f /var/www/.env.docker ]; then
        cp /var/www/.env.docker /var/www/.env
    elif [ -f /var/www/.env.example ]; then
        cp /var/www/.env.example /var/www/.env
    fi
fi

# Run storage link if public/storage does not exist
if [ ! -L /var/www/public/storage ]; then
    php /var/www/artisan storage:link || true
fi

# Execute passed command (default: php-fpm)
exec "$@"
