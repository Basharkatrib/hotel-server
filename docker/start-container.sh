#!/usr/bin/env sh
set -e

# Configure Nginx to Render's dynamic port
mkdir -p /etc/nginx/http.d
envsubst '${PORT}' < /etc/nginx/http.d/default.conf.template > /etc/nginx/http.d/default.conf

# Laravel runtime prep
cd /app
php artisan storage:link || true

# Publish Filament assets (CSS, JS, Fonts)
php artisan filament:upgrade || true
php artisan vendor:publish --tag=filament-assets --force || true

# Ensure public directory has correct permissions
chown -R www-data:www-data /app/public || true
chmod -R 755 /app/public || true

php artisan config:cache
# php artisan route:cache
php artisan view:cache
php artisan event:cache
# Uncomment if you want automatic schema updates on deploy
# php artisan migrate --force

exec /usr/bin/supervisord -c /etc/supervisord.conf


