# syntax=docker/dockerfile:1.7
#
# Targets:
#   dev   PHP-FPM with Composer and development settings (source is bind-mounted by compose)
#   test  dev + PCOV and the full dependency set baked in, for CI coverage runs
#   prod  immutable, non-root image with production OPcache and no dev dependencies
#
# Build from the backend/ directory:
#   docker build -f docker/Dockerfile --target prod -t expenses-backend:prod .

ARG PHP_VERSION=8.5.7
ARG ALPINE_VERSION=3.23
ARG COMPOSER_VERSION=2.10.1

FROM composer:${COMPOSER_VERSION} AS composer

# ---------------------------------------------------------------------------
# base: PHP-FPM plus the extensions the application needs
# ---------------------------------------------------------------------------
FROM php:${PHP_VERSION}-fpm-alpine${ALPINE_VERSION} AS base

RUN apk add --no-cache libpq \
    && apk add --no-cache --virtual .build-deps libpq-dev \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql pdo_pgsql \
    && apk del .build-deps

WORKDIR /var/www/html

# ---------------------------------------------------------------------------
# dev
# ---------------------------------------------------------------------------
FROM base AS dev

COPY --from=composer /usr/bin/composer /usr/bin/composer
RUN apk add --no-cache git unzip \
    && cp "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
COPY docker/php/dev.ini "$PHP_INI_DIR/conf.d/zz-app.ini"

# ---------------------------------------------------------------------------
# test: dev + PCOV, dependencies installed from the lock file
# ---------------------------------------------------------------------------
FROM dev AS test

RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install pcov \
    && docker-php-ext-enable pcov \
    && apk del .build-deps

COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-progress --no-scripts --prefer-dist
COPY . .
RUN composer dump-autoload --no-interaction \
    && chown -R www-data:www-data /var/www/html

USER www-data
CMD ["php", "-d", "pcov.enabled=1", "vendor/bin/pest", "--coverage", "--min=90"]

# ---------------------------------------------------------------------------
# vendor: production dependencies only, with an optimized autoloader
# ---------------------------------------------------------------------------
FROM composer AS vendor

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --no-scripts --no-autoloader --prefer-dist
COPY app ./app
COPY bootstrap ./bootstrap
COPY config ./config
COPY database ./database
COPY routes ./routes
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative --no-interaction --no-scripts

# ---------------------------------------------------------------------------
# prod
# ---------------------------------------------------------------------------
FROM base AS prod

RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php/prod.ini "$PHP_INI_DIR/conf.d/zz-app.ini"
COPY docker/php/entrypoint.sh /usr/local/bin/app-entrypoint
RUN chmod +x /usr/local/bin/app-entrypoint

COPY --from=vendor --chown=www-data:www-data /app/vendor ./vendor
COPY --chown=www-data:www-data artisan composer.json ./
COPY --chown=www-data:www-data app ./app
COPY --chown=www-data:www-data bootstrap ./bootstrap
COPY --chown=www-data:www-data config ./config
COPY --chown=www-data:www-data database ./database
COPY --chown=www-data:www-data lang ./lang
COPY --chown=www-data:www-data public ./public
COPY --chown=www-data:www-data resources ./resources
COPY --chown=www-data:www-data routes ./routes
COPY --chown=www-data:www-data storage ./storage

RUN mkdir -p database/sqlite storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data database/sqlite storage bootstrap/cache

# Config, route, event and view caches are built at start-up (see the entrypoint)
# because they must capture the runtime environment, not the build environment.
USER www-data
ENTRYPOINT ["app-entrypoint"]
CMD ["php-fpm"]
