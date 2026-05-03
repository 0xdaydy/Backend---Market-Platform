#!/bin/sh
set -e

# Bootstrap Laravel on container start
# (env vars are available at runtime, not build time)

# Generate key if APP_KEY is empty (fresh deploy)
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:..." ]; then
    php artisan key:generate --force
fi

# Cache for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Run pending migrations
php artisan migrate --force

# Start supervisord (runs Nginx + PHP-FPM + queue worker)
exec /usr/bin/supervisord -c /etc/supervisord.conf
