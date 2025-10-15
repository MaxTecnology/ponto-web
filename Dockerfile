# syntax=docker/dockerfile:1.6

FROM composer:2 AS vendor

WORKDIR /var/www/html

COPY composer.json composer.lock ./
ENV COMPOSER_ALLOW_SUPERUSER=1

RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --no-progress \
    --no-scripts \
    --optimize-autoloader

COPY . .

RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --no-progress \
    --optimize-autoloader


FROM node:20-alpine AS frontend

WORKDIR /var/www/html

COPY package.json package-lock.json ./

RUN npm ci --no-audit --no-fund

COPY resources resources
COPY vite.config.js ./
COPY public public

RUN npm run build


FROM php:8.3-fpm-alpine AS app

RUN apk add --no-cache \
    bash \
    curl \
    git \
    icu-dev \
    icu-data-full \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    oniguruma-dev \
    su-exec \
    supervisor

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        bcmath \
        intl \
        gd \
        opcache \
        pdo_mysql \
        zip

WORKDIR /var/www/html

COPY --from=vendor /var/www/html /var/www/html
COPY --from=frontend /var/www/html/public/build /var/www/html/public/build

COPY docker/prod/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/prod/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN chmod +x /usr/local/bin/entrypoint.sh \
    && mkdir -p storage/framework/{cache,sessions,testing,views} \
    && mkdir -p storage/app/public \
    && mkdir -p storage/logs \
    && touch storage/logs/laravel.log \
    && ln -sfn /var/www/html/storage/app/public /var/www/html/public/storage \
    && chown -R www-data:www-data storage bootstrap/cache

ENV APP_ENV=production \
    APP_DEBUG=false \
    PHP_OPCACHE_VALIDATE_TIMESTAMPS=0 \
    PHP_OPCACHE_MAX_ACCELERATED_FILES=20000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm", "-F", "-O"]


FROM nginx:1.25-alpine AS web

WORKDIR /var/www/html

COPY --from=app /var/www/html /var/www/html
COPY docker/prod/nginx/default.conf /etc/nginx/conf.d/default.conf

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
