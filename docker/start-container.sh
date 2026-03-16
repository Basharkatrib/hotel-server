#!/usr/bin/env sh
set -e

# Configure Nginx to Render's dynamic port
mkdir -p /etc/nginx/http.d
envsubst '${PORT}' < /etc/nginx/http.d/default.conf.template > /etc/nginx/http.d/default.conf

# Laravel runtime prep
cd /app
php artisan storage:link || true

# Clear any cached config first to ensure fresh state
php artisan config:clear || true
php artisan cache:clear || true

# Publish Filament assets (CSS, JS, Fonts) - MUST be before config:cache
# Try multiple methods to ensure assets are published
php artisan filament:upgrade --no-interaction --force || true
php artisan vendor:publish --tag=filament-assets --force --no-interaction || true
php artisan vendor:publish --provider="Filament\FilamentServiceProvider" --tag=filament-assets --force --no-interaction || true

# Verify Filament assets exist, if not, try alternative method
if [ ! -d "/app/public/css/filament" ] || [ ! -d "/app/public/js/filament" ]; then
    echo "Warning: Filament assets not found, attempting alternative publish method..."
    php artisan optimize:clear || true
    php artisan filament:upgrade --no-interaction --force || true
fi

# Ensure public directory has correct permissions
chown -R www-data:www-data /app/public || true
chmod -R 755 /app/public || true

# List Filament assets for debugging
ls -la /app/public/css/filament 2>/dev/null || echo "Filament CSS directory not found"
ls -la /app/public/js/filament 2>/dev/null || echo "Filament JS directory not found"
ls -la /app/public/fonts/filament 2>/dev/null || echo "Filament Fonts directory not found"

# Now cache config
php artisan config:cache
# php artisan route:cache
php artisan view:cache
php artisan event:cache
# Uncomment if you want automatic schema updates on deploy
# php artisan migrate --force

exec /usr/bin/supervisord -c /etc/supervisord.conf


