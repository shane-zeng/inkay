ARG PHP_VERSION=8.2

FROM composer:latest AS vendor

WORKDIR /var/www/html

COPY composer* ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --ignore-platform-reqs \
    --optimize-autoloader \
    --apcu-autoloader \
    --ansi \
    --no-scripts \
    --audit

FROM php:${PHP_VERSION}-alpine3.18

LABEL maintainer="Shane"

ENV ROOT=/var/www/html
WORKDIR $ROOT

SHELL ["/bin/ash", "-e", "-c"]

COPY . .

ARG WWWUSER=1000
ARG WWWGROUP=1000

RUN addgroup -g $WWWGROUP -S inkay || true \
    && adduser -D -h /home/inkay -s /bin/ash -G inkay -u $WWWUSER inkay

RUN mkdir -p \
        storage/framework/sessions \
        storage/framework/views \
        storage/framework/cache/data \
        storage/logs \
        bootstrap/cache \
    && chown -R inkay:inkay \
        storage \
        bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache

COPY deployment/app-entrypoint.sh deployment/app-entrypoint.sh
RUN chmod +x deployment/app-entrypoint.sh

COPY --from=vendor ${ROOT}/vendor vendor

USER inkay

ENTRYPOINT ["deployment/app-entrypoint.sh"]
