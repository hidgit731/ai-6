FROM php:8.4-fpm-alpine

ARG PUID=1000
ARG PGID=1000

# $PHPIZE_DEPS provides autoconf/gcc/make needed by pecl install
# linux-headers is required by xdebug
RUN apk add --no-cache \
        libpq-dev \
        icu-dev \
        oniguruma-dev \
        linux-headers \
        $PHPIZE_DEPS \
    && docker-php-ext-install \
        pdo_pgsql \
        opcache \
        intl \
        mbstring \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apk del $PHPIZE_DEPS

COPY --from=composer:2.9.5 /usr/bin/composer /usr/local/bin/composer

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
COPY php/php.ini /usr/local/etc/php/conf.d/custom.ini

# Xdebug in trigger mode — does not slow down every request
RUN echo "xdebug.mode=debug" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.start_with_request=trigger" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

RUN addgroup -g ${PGID} appgroup \
    && adduser -u ${PUID} -G appgroup -s /bin/sh -D appuser

USER appuser
# Source code is mounted via volume — no COPY needed
