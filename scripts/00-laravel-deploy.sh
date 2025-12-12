#!/usr/bin/env bash

echo "Generating application key..."
php artisan key:generate --ansi

echo "Caching configuration..."
php artisan config:cache

echo "Caching routes..."
php artisan route:cache

echo "Running migrations..."
php artisan migrate --force

echo "Running seeders..."
php artisan db:seed --force

# تشغيل PHP-FPM
php-fpm
