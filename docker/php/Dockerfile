# syntax=docker/dockerfile:1.7

ARG PHP_VERSION=8.3

########################################################################
# base — shared runtime with the production extension set
########################################################################
FROM php:${PHP_VERSION}-fpm-alpine AS base

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_MEMORY_LIMIT=-1 \
    APP_RUNTIME_DIR=/var/www/app

WORKDIR ${APP_RUNTIME_DIR}

# System libraries required by the PHP extensions below.
RUN apk add --no-cache \
        acl \
        fcgi \
        gmp \
        icu-libs \
        libpq \
        rabbitmq-c \
    && rm -rf /var/cache/apk/*

# Compile extensions in a throwaway build layer, keep only the .so files.
RUN --mount=type=cache,target=/var/cache/apk \
    apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        gmp-dev \
        icu-dev \
        linux-headers \
        postgresql-dev \
        rabbitmq-c-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        intl \
        opcache \
        pdo_pgsql \
        pcntl \
        sockets \
    && pecl install apcu redis amqp \
    && docker-php-ext-enable apcu redis amqp \
    && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php/fpm/php.ini            /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php/fpm/www.conf           /usr/local/etc/php-fpm.d/zz-www.conf
COPY docker/php/fpm/docker-healthcheck /usr/local/bin/docker-healthcheck

RUN chmod +x /usr/local/bin/docker-healthcheck \
    && addgroup -g 1000 app \
    && adduser -u 1000 -G app -s /bin/sh -D app \
    && mkdir -p /var/run/php var/cache var/log \
    && chown -R app:app ${APP_RUNTIME_DIR} /var/run/php

HEALTHCHECK --interval=10s --timeout=3s --retries=3 CMD ["docker-healthcheck"]

########################################################################
# dev — adds Xdebug + Composer dev dependencies, source is bind-mounted
########################################################################
FROM base AS dev

ENV APP_ENV=dev APP_DEBUG=1 XDEBUG_MODE=off

RUN --mount=type=cache,target=/var/cache/apk \
    apk add --no-cache --virtual .build-deps $PHPIZE_DEPS linux-headers \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apk del .build-deps

COPY docker/php/fpm/xdebug.ini /usr/local/etc/php/conf.d/zz-xdebug.ini

USER app
CMD ["php-fpm", "--nodaemonize"]

########################################################################
# prod — self-contained image with vendor/ and warmed cache baked in
########################################################################
FROM base AS prod

ENV APP_ENV=prod APP_DEBUG=0

COPY composer.json composer.lock* symfony.lock* ./
RUN composer install --no-dev --no-scripts --no-progress --prefer-dist --no-autoloader

COPY --chown=app:app . .

RUN composer dump-autoload --no-dev --classmap-authoritative \
    && composer run-script --no-dev post-install-cmd \
    && sync

USER app
CMD ["php-fpm", "--nodaemonize"]
