#!/bin/sh
set -e

export APP_ENV=prod
export APP_DEBUG=0

echo "APP_ENV: $APP_ENV"

echo "Clearing old cache..."
rm -rf var/cache/*

echo "Dumping env..."
composer dump-env prod

echo "Warming up cache..."
php bin/console cache:warmup --env=prod --no-debug

echo "Running migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --env=prod --no-debug

echo "Starting services..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf