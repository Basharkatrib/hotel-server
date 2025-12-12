# استخدم PHP 8.2 FPM الرسمي
FROM php:8.2-fpm

# تثبيت مكتبات النظام المطلوبة لـ PostgreSQL و Laravel
RUN apt-get update && apt-get install -y \
    libpq-dev \
    unzip \
    git \
    curl \
    zip \
    && docker-php-ext-install pdo_pgsql

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
COPY start.sh /start.sh
RUN chmod +x /start.sh

# فتح المنفذ
EXPOSE 9000

CMD ["/start.sh"]
