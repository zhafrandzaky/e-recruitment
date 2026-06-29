# --- Stage 1: PHP dependencies ---
FROM composer:2.10 AS composer-deps
WORKDIR /app
COPY apps/api/composer.json apps/api/composer.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --ignore-platform-reqs \
    --prefer-dist

# --- Stage 2: Production image ---
FROM php:8.4-fpm-alpine AS production

RUN apk add --no-cache \
    libpq-dev \
    libzip-dev \
    unzip \
    && docker-php-ext-install \
    pdo \
    pdo_pgsql \
    zip \
    opcache \
    pcntl \
    && docker-php-ext-enable opcache

RUN apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

WORKDIR /var/www/html

COPY apps/api .
COPY --from=composer-deps /app/vendor ./vendor

RUN composer dump-autoload --classmap-authoritative --no-dev

RUN addgroup -g 1001 -S www && adduser -S www -u 1001 -G www
RUN chown -R www:www /var/www/html/storage /var/www/html/bootstrap/cache

USER www

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
