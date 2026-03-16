FROM php:8.4-cli

RUN apt-get update && apt-get install -y \
    libicu-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-install intl zip pdo_mysql opcache

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# انسخ كل شيء أولاً
COPY . .

# ثم شغّل composer
RUN composer install --optimize-autoloader --no-dev --no-interaction

RUN php artisan optimize
RUN php artisan filament:optimize

EXPOSE 8080

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8080"]