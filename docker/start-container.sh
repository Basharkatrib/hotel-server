#!/usr/bin/env sh
set -e

# Configure Nginx port
mkdir -p /etc/nginx/http.d
envsubst '${PORT}' < /etc/nginx/http.d/default.conf.template > /etc/nginx/http.d/default.conf

cd /app

# Storage link (مرة واحدة فقط)
php artisan storage:link || true

# Permissions
chown -R www-data:www-data /app/storage /app/bootstrap/cache /app/public || true

# Optimize (بناء الـ cache مرة واحدة)
php artisan optimize
php artisan filament:optimize

# Migrations (اختياري - فعّله إذا أردت)
# php artisan migrate --force

exec /usr/bin/supervisord -c /etc/supervisord.conf