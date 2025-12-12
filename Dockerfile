FROM php:8.4-fpm

# تثبيت مكتبات النظام المطلوبة
RUN apt-get update && apt-get install -y \
    libpq-dev \
    unzip \
    git \
    curl \
    zip \
    libzip-dev \
    libicu-dev \
    && docker-php-ext-install pdo_pgsql intl zip

# تثبيت Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# تعيين مجلد العمل
WORKDIR /var/www/html

# نسخ المشروع
COPY . .

# تثبيت الحزم الخاصة بـ Laravel 12
RUN composer install --no-dev --optimize-autoloader

# إعداد الصلاحيات لمجلدات Laravel
RUN chown -R www-data:www-data storage bootstrap/cache
RUN chmod -R 755 storage bootstrap/cache

# نسخ سكربت البداية
COPY scripts/00-laravel-deploy.sh /start.sh
RUN chmod +x /start.sh

# فتح المنفذ
EXPOSE 9000

CMD ["/start.sh"]
