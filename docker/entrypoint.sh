#!/bin/sh
set -e

# -------------------------------------------------------
# Initialize shared public volume on first boot
# -------------------------------------------------------
if [ ! -f /var/www/html/public/index.php ]; then
    echo "Initializing public directory..."
    cp -rp /var/www/public-init/. /var/www/html/public/
    chown -R www-data:www-data /var/www/html/public
    echo "Public directory initialized."
fi

# -------------------------------------------------------
# Wait for PostgreSQL
# -------------------------------------------------------
DB_HOST="${DB_HOST:-postgres}"
DB_PORT="${DB_PORT:-5432}"

echo "Waiting for database at ${DB_HOST}:${DB_PORT}..."
until nc -z "$DB_HOST" "$DB_PORT" 2>/dev/null; do
    sleep 1
done
echo "Database is ready."

# -------------------------------------------------------
# Ensure storage directory structure exists
# -------------------------------------------------------
mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs
chown -R www-data:www-data storage bootstrap/cache

# -------------------------------------------------------
# Regenerate the package manifest so it always matches the
# dependencies actually installed in this container (avoids
# stale dev/no-dev provider mismatches in bootstrap/cache).
# -------------------------------------------------------
php artisan package:discover --ansi

# -------------------------------------------------------
# Run migrations
# -------------------------------------------------------
echo "Running migrations..."
php artisan migrate --force --no-interaction

# -------------------------------------------------------
# Cache config/routes in production
# -------------------------------------------------------
if [ "${APP_ENV}" = "production" ]; then
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

# -------------------------------------------------------
# Start PHP-FPM
# -------------------------------------------------------
echo "Starting PHP-FPM..."
exec php-fpm
