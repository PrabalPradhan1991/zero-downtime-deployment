# ─────────────────────────────────────────────────────────────
#  Stage 1 — Composer dependencies
# ─────────────────────────────────────────────────────────────
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader

# ─────────────────────────────────────────────────────────────
#  Stage 2 — Runtime image
# ─────────────────────────────────────────────────────────────
FROM php:8.3-fpm-alpine

# System & PHP extensions
RUN apk add --no-cache \
        nginx \
        supervisor \
        bash \
        curl \
        libpng-dev \
        libzip-dev \
        oniguruma-dev \
        libxml2-dev \
        librdkafka-dev \
        autoconf \
        gcc \
        make \
        g++ \
    && pecl install rdkafka \
    && docker-php-ext-enable rdkafka \
    && apk del autoconf gcc make g++ \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        zip \
        opcache \
    && rm -rf /var/cache/apk/* \
    && mkdir -p /var/log/supervisor

# OPcache tuning for Laravel
RUN { \
    echo "opcache.enable=1"; \
    echo "opcache.memory_consumption=128"; \
    echo "opcache.interned_strings_buffer=8"; \
    echo "opcache.max_accelerated_files=10000"; \
    echo "opcache.revalidate_freq=0"; \
    echo "opcache.fast_shutdown=1"; \
} > /usr/local/etc/php/conf.d/opcache.ini

# ── Nginx config ──────────────────────────────────────────────
COPY docker/nginx.conf /etc/nginx/nginx.conf

# ── Supervisor config (manages nginx + php-fpm) ───────────────
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# ── Application ───────────────────────────────────────────────
WORKDIR /var/www/html

COPY --from=vendor /app/vendor ./vendor
COPY . .

# Generate app key + cache config for production
RUN cp .env.example .env \
    && php artisan key:generate \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 8000

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]