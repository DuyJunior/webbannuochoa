# syntax=docker/dockerfile:1
FROM php:8.3-fpm-bookworm AS php-base

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates curl gettext-base gosu nginx tini util-linux unzip \
        libonig-dev libxml2-dev libzip-dev \
    && docker-php-ext-install -j"$(nproc)" bcmath dom mbstring opcache pcntl pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-production.ini
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/zzzz-app.conf

FROM php-base AS vendor
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --no-scripts --no-autoloader
COPY . .
RUN composer dump-autoload --no-dev --optimize --strict-psr --no-scripts \
    && composer check-platform-reqs --no-dev

FROM node:22-bookworm-slim AS frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY . .
# Tailwind scans Laravel's pagination views via resources/css/app.css.
COPY --from=vendor /var/www/html/vendor/laravel/framework/src/Illuminate/Pagination/resources/views /app/vendor/laravel/framework/src/Illuminate/Pagination/resources/views
RUN npm run build && test -s public/build/manifest.json

FROM php-base AS production
ENV APP_ENV=production \
    APP_DEBUG=false \
    DEMO_MODE=false \
    LOG_CHANNEL=stderr \
    LOG_LEVEL=info \
    DB_CONNECTION=mysql \
    SESSION_DRIVER=database \
    SESSION_SECURE_COOKIE=true \
    CACHE_STORE=database \
    QUEUE_CONNECTION=database \
    DB_QUEUE_RETRY_AFTER=90 \
    MYSQL_ATTR_SSL_VERIFY_SERVER_CERT=true \
    RUN_MIGRATIONS=true \
    RUN_SEEDERS=false \
    PORT=10000

COPY --from=vendor /var/www/html /var/www/html
COPY --from=frontend /app/public/build /var/www/html/public/build
COPY docker/nginx.conf /etc/nginx/nginx.conf.template
COPY --chmod=0755 docker/entrypoint.sh /usr/local/bin/app-entrypoint
COPY --chmod=0755 docker/supervise.sh /usr/local/bin/app-supervise
RUN mkdir -p /var/www/private /var/www/html/storage/app/public \
        /var/www/html/storage/framework/cache/data /var/www/html/storage/framework/sessions \
        /var/www/html/storage/framework/views /var/www/html/storage/logs \
        /var/www/html/bootstrap/cache /var/www/html/public/images/products \
        /var/www/html/public/images/videos \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
        /var/www/html/public/images/products /var/www/html/public/images/videos \
    && chmod -R ug+rwX,o-rwx /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod 0750 /var/www/private \
    && chown root:www-data /var/www/private

EXPOSE 10000
STOPSIGNAL SIGTERM
HEALTHCHECK --interval=30s --timeout=5s --start-period=90s --retries=3 \
    CMD curl --fail --silent --output /dev/null "http://127.0.0.1:${PORT}/up" || exit 1
ENTRYPOINT ["/usr/bin/tini", "--", "/usr/local/bin/app-entrypoint"]
