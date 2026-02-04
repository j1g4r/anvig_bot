#!/bin/sh

# Exit on fail
set -e

# Wait for DB to be ready might be good, but for now we rely on compose healthchecks or simple retry

# Install dependencies if vendor is missing (for local dev mostly)
if [ ! -d "vendor" ]; then
    composer install --no-progress --no-interaction
fi

if [ ! -f ".env" ]; then
    cp .env.example .env
fi

# Run Migrations
echo "Running Migrations..."
php artisan migrate --force

# Cache config
echo "Caching Configuration..."
php artisan config:cache
php artisan route:cache

# Start Supervisor (which starts Nginx, PHP-FPM, Workers, Reverb)
echo "Starting Supervisor..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
