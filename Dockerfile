ARG IMAGE_VARIANT=fpm-nginx

FROM serversideup/php:8.3-${IMAGE_VARIANT}-v3.5.2 AS base
USER root
# Install system and PHP dependencies, clean up in one layer
RUN apt-get update \
    && apt-get -y --no-install-recommends install lftp \
    && install-php-extensions exif ftp gd gmp memcached sockets ldap \
    && apt-get purge -y --auto-remove autoconf g++ make \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

WORKDIR /var/www/html
USER www-data
# Composer JSON file
COPY composer.json composer.lock ./
# Install composer dependencies
RUN composer install --no-interaction --no-plugins --no-scripts --prefer-dist --optimize-autoloader

# ---- Frontend Builder Stage ----
FROM node:20-alpine AS frontend-builder

WORKDIR /app
COPY --from=base /var/www/html/vendor/tightenco/ziggy/dist/ ./vendor/tightenco/ziggy/dist/

# Frontend files copy
COPY package.json package-lock.json vite.config.ts tailwind.config.js postcss.config.js jsconfig.json ./

# Frontend dependencies install
RUN npm ci

# Building frontend
COPY resources ./resources
ARG VITE_AZURE_AD_CLIENT_ID
ARG VITE_AZURE_AD_TENANT_ID
ARG VITE_FRONTEND_HOST
RUN npm run build

# Production image
FROM base AS prod
COPY --from=frontend-builder /app/public/build /var/www/html/public/build
# COPY --from=backend-builder /app/vendor /var/www/html/vendor
# COPY . /var/www/html
COPY --chown=www-data:www-data . .
# COPY --from=backend-builder --chown=www-data:www-data /app/vendor ./vendor
# COPY --from=frontend-builder --chown=www-data:www-data /app/public/build ./public/build

# Development image
FROM base AS dev
USER root
RUN docker-php-serversideup-set-id www-data 1000:1000 && \
    docker-php-serversideup-set-file-permissions --owner 1000:1000 --service nginx
USER www-data