## Multi-stage Docker image for Laravel on Render (Nginx + PHP-FPM via supervisord)
## - Stage 1: Composer dependencies
## - Stage 2: Frontend build (Vite) - optional
## - Stage 3: Final runtime (php-fpm + nginx + supervisord)

# ------------------------------
# Stage 1: PHP dependencies
# ------------------------------
FROM composer:2 AS vendor
WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-progress --no-interaction --no-scripts --ignore-platform-reqs

# copy full app (for optimized autoload)
COPY . .
RUN composer dump-autoload --optimize

# ------------------------------
# Stage 2: Frontend build (optional)
# ------------------------------
FROM node:20-alpine AS assets
WORKDIR /app

# Copy lock files if present to leverage caching
COPY package.json ./
COPY package-lock.json* ./
COPY pnpm-lock.yaml* ./
COPY yarn.lock* ./

RUN if [ -f package.json ]; then \
      if [ -f pnpm-lock.yaml ]; then npm i -g pnpm && pnpm i --frozen-lockfile; \
      elif [ -f yarn.lock ]; then npm i -g yarn && yarn install --frozen-lockfile; \
      elif [ -f package-lock.json ]; then npm ci; \
      else npm install; fi; \
    fi

COPY . .
RUN if [ -f package.json ]; then \
      if [ -f pnpm-lock.yaml ]; then pnpm build; \
      elif [ -f yarn.lock ]; then yarn build; \
      else npm run build; fi; \
    fi; \
    mkdir -p /app/public/build

# ------------------------------
# Stage 3: Runtime
# ------------------------------
FROM php:8.3-fpm-alpine AS runtime

WORKDIR /app

# System and PHP extensions
RUN set -eux; \
    apk add --no-cache --virtual .build-deps \
      $PHPIZE_DEPS \
      icu-dev \
      zlib-dev \
      libzip-dev \
      libpng-dev \
      libjpeg-turbo-dev \
      libwebp-dev \
      freetype-dev; \
    apk add --no-cache \
      nginx \
      supervisor \
      git \
      curl \
      icu-libs \
      libzip \
      libpng \
      libjpeg-turbo \
      libwebp \
      freetype \
      tzdata \
      bash \
      gettext; \
    docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp; \
    docker-php-ext-install -j$(nproc) \
      pdo_mysql \
      bcmath \
      exif \
      intl \
      gd \
      zip \
      opcache; \
    apk del .build-deps; \
    rm -rf /var/cache/apk/*

# Configure php-fpm for production a bit (opcache)
COPY --chown=www-data:www-data . /app

# Bring in vendor and built assets from previous stages
COPY --from=vendor /app/vendor /app/vendor
COPY --from=vendor /app/bootstrap/cache /app/bootstrap/cache
COPY --from=assets /app/public/build /app/public/build

# Nginx + Supervisor configs and entrypoint
RUN mkdir -p /etc/nginx/http.d
COPY docker/nginx.conf.template /etc/nginx/http.d/default.conf.template
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/start-container.sh /usr/local/bin/start-container.sh

RUN chmod +x /usr/local/bin/start-container.sh && \
    mkdir -p /run/nginx && \
    chown -R www-data:www-data /app/storage /app/bootstrap/cache

# Render will provide PORT; we don't EXPOSE a fixed port
CMD ["start-container.sh"]


