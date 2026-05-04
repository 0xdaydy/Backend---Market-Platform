#!/bin/sh

# Bootstrap Laravel on container start
# (env vars are available at runtime, not build time)

# Ensure storage/cache permissions
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Generate key if APP_KEY is empty (fresh deploy)
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:..." ]; then
    echo "Generating APP_KEY..."
    php artisan key:generate --force
fi

# Cache for production
echo "Caching config..."
php artisan config:cache || echo "WARNING: config:cache failed"

echo "Caching routes..."
php artisan route:cache || echo "WARNING: route:cache failed"

echo "Caching views..."
php artisan view:cache || echo "WARNING: view:cache failed"

echo "Caching events..."
php artisan event:cache || echo "WARNING: event:cache failed"

# Run pending migrations
echo "Running migrations..."
php artisan migrate --force || echo "WARNING: migrate failed"

# Start supervisord (runs Nginx + PHP-FPM + queue worker)
echo "Starting supervisord..."
exec /usr/bin/supervisord -c /etc/supervisord.conf
