#!/bin/sh
set -e

echo "APP_ENV: $APP_ENV"

echo "Clearing old cache..."
rm -rf var/cache/prod

echo "Warming up cache..."
php bin/console cache:warmup --env=prod --no-debug

echo "Running migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --env=prod

echo "Starting services..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf