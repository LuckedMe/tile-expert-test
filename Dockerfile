# syntax=docker/dockerfile:1

# Production image of the Tile Expert test API (Symfony).
#
# Targets:
#   app   (default) - php-fpm with the application code and vendors
#   nginx           - nginx serving public/ and proxying PHP to the "php-fpm" host
#
#   docker build -t tile-expert-test:latest .
#   docker build -t tile-expert-test-nginx:latest --target nginx .
#
# The local development stack keeps using docker-compose.yaml and .docker/*/Dockerfile.
# How to keep this file in sync with the project: docs/main-dockerfile-fix.md.

ARG PHP_VERSION=8.4

###############################################################################
# PHP runtime: extensions shared by the build stages and the final image
###############################################################################
FROM php:${PHP_VERSION}-fpm AS php-base

ARG USER_ID=1000
ARG GROUP_ID=1000

ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN apt-get update \
    && apt-get install -y --no-install-recommends ca-certificates git unzip \
    && install-php-extensions @composer zip opcache intl pdo_mysql bcmath gd exif xsl \
    && rm -rf /var/lib/apt/lists/*

RUN groupadd app -g ${GROUP_ID} \
    && useradd app -u ${USER_ID} -g ${GROUP_ID} -m -s /bin/bash

COPY .docker/php-fpm/config/php.ini /usr/local/etc/php/php.ini
COPY .docker/php-fpm/config/php-fpm.d/* /usr/local/etc/php-fpm.d/
COPY .docker/php-fpm/config/php.prod.ini /usr/local/etc/php/conf.d/zz-prod.ini

ENV APP_ENV=prod \
    APP_DEBUG=0 \
    COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_NO_INTERACTION=1

WORKDIR /var/www/html

###############################################################################
# Composer dependencies (cached until composer.json / composer.lock change)
###############################################################################
FROM php-base AS vendor

COPY composer.json composer.lock symfony.lock ./

RUN --mount=type=cache,target=/root/.composer/cache \
    composer install --no-dev --prefer-dist --no-autoloader --no-scripts --no-progress

###############################################################################
# Application image (php-fpm)
###############################################################################
FROM php-base AS app

COPY --from=vendor /var/www/html/vendor ./vendor
COPY . .

# composer auto-scripts: cache:clear and assets:install public (Swagger UI files of NelmioApiDocBundle);
# the cache is removed again: the entrypoint warms it at start, with the real environment of the container
RUN composer dump-autoload --no-dev --classmap-authoritative \
    && composer run-script --no-dev post-install-cmd \
    && rm -rf var/cache/prod \
    && mkdir -p var/cache var/log var/share \
    && chown -R app:app var

COPY --chmod=0755 .docker/php-fpm/docker-entrypoint.sh /usr/local/bin/docker-entrypoint

USER app:app

EXPOSE 9000

ENTRYPOINT ["docker-entrypoint"]
CMD ["php-fpm"]

###############################################################################
# Web server: static files from the app image + fastcgi to php-fpm:9000
###############################################################################
FROM nginx:mainline-alpine AS nginx

COPY .docker/nginx/config/nginx.conf /etc/nginx/nginx.conf
COPY .docker/nginx/config/conf.d/default.conf /etc/nginx/conf.d/default.conf
COPY --from=app /var/www/html/public /var/www/html/public

WORKDIR /var/www/html

###############################################################################
# Keep "app" as the default target of a plain `docker build .`
###############################################################################
FROM app
