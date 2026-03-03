FROM php:8.4-fpm-alpine AS builder

# $PHPIZE_DEPS provides build tools needed by docker-php-ext-install for intl/mbstring
RUN apk add --no-cache \
        libpq-dev \
        icu-dev \
        oniguruma-dev \
        $PHPIZE_DEPS \
    && docker-php-ext-install \
        pdo_pgsql \
        opcache \
        intl \
        mbstring \
    && apk del $PHPIZE_DEPS

COPY --from=composer:2.9.5 /usr/bin/composer /usr/local/bin/composer

WORKDIR /app
COPY . .
RUN composer install --no-dev --optimize-autoloader --no-interaction


FROM php:8.4-fpm-alpine AS production

ARG PUID=1000
ARG PGID=1000

RUN apk add --no-cache \
        libpq-dev \
        icu-dev \
        oniguruma-dev \
        $PHPIZE_DEPS \
    && docker-php-ext-install \
        pdo_pgsql \
        opcache \
        intl \
        mbstring \
    && apk del $PHPIZE_DEPS

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" && rm "$PHP_INI_DIR/php.ini-development"
COPY php/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY --from=builder /app /app

RUN addgroup -g ${PGID} appgroup \
    && adduser -u ${PUID} -G appgroup -s /bin/sh -D appuser

USER appuser
WORKDIR /app
