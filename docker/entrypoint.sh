#!/bin/bash
set -e

# SECURITY: Exit on any error to prevent corruption
set -o pipefail

echo "[$(date +'%Y-%m-%d %H:%M:%S')] Starting Laravel application entrypoint..."

# Check required environment variables for security
if [ -z "$APP_KEY" ]; then
    echo "[ERROR] APP_KEY is not set. Please configure it in your environment variables."
    exit 1
fi

if [ -z "$DATABASE_URL" ] && [ -z "$DB_CONNECTION" ]; then
    echo "[ERROR] Database configuration is missing (DATABASE_URL or DB_CONNECTION required)."
    exit 1
fi

# Generate cache for production
echo "[$(date +'%Y-%m-%d %H:%M:%S')] Generating Laravel caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run database migrations only if APP_ENV is not 'production' or SKIP_MIGRATIONS is not set
if [ "$APP_ENV" != "production" ] || [ "$SKIP_MIGRATIONS" != "true" ]; then
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] Running database migrations..."
    php artisan migrate --force || {
        echo "[WARNING] Migration failed. This might be expected in first deployment or if database is not ready."
    }
else
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] Skipping migrations (APP_ENV=production and SKIP_MIGRATIONS=true)"
fi

echo "[$(date +'%Y-%m-%d %H:%M:%S')] Starting services via Supervisor..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
