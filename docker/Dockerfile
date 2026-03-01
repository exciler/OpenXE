# syntax=docker/dockerfile:1.20

# -- Base image stage --
FROM php:8.2-apache AS base

ARG PHP_EXT="bcmath exif gd ldap imap mysqli pcntl pdo_mysql soap zip opcache"

# Install system dependencies and PHP extensions
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions ${PHP_EXT}

# Enable Apache modules
RUN a2enmod rewrite headers

# Configure PHP (general settings)
COPY docker/php/memory.ini "$PHP_INI_DIR/conf.d/memory.ini"

# Set up working directory
WORKDIR /var/www/html

# -- Frontend build stage --
FROM node:20-alpine AS frontend
WORKDIR /app
COPY package.json package-lock.json vite.config.js /app/
RUN npm clean-install
COPY classes /app/classes
COPY resources /app/resources
COPY www /app/www
RUN npm run build

# -- PHP dependencies (Composer) stage --
FROM composer:2 AS composer_base
WORKDIR /app
COPY --parents composer.json composer.lock conf phpwf www /app/

# -- Dev-specific PHP dependencies --
FROM composer_base AS composer_dev
RUN composer install --no-interaction --no-scripts --no-progress

# -- Prod-specific PHP dependencies --
FROM composer_base AS composer_prod
RUN composer install --no-interaction --no-scripts --no-progress --no-dev --optimize-autoloader

# -- Development stage --
FROM base AS dev
ENV APP_ENV=dev
ENV APP_DEBUG=1

# Install Xdebug
RUN install-php-extensions xdebug

# Use development PHP config
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

# Create a local development-friendly environment
# (Volumes are usually mounted here in docker-compose)
COPY --chown=www-data:www-data --exclude=userdata . /var/www/html
COPY --chown=www-data:www-data --from=frontend /app/www/dist /var/www/html/www/dist
COPY --from=composer_dev /app/vendor /var/www/html/vendor

# -- Production stage --
FROM base AS prod
ENV APP_ENV=prod
ENV APP_DEBUG=0

# Use production PHP config
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Configure OPcache for production (Symfony best practice)
RUN { \
    echo 'opcache.memory_consumption=256'; \
    echo 'opcache.interned_strings_buffer=16'; \
    echo 'opcache.max_accelerated_files=20000'; \
    echo 'opcache.revalidate_freq=0'; \
    echo 'opcache.validate_timestamps=0'; \
    echo 'opcache.fast_shutdown=1'; \
    } > "$PHP_INI_DIR/conf.d/opcache-optimized.ini"

# Copy source code and artifacts
COPY --chown=www-data:www-data --exclude=userdata . /var/www/html
COPY --chown=www-data:www-data --from=frontend /app/www/dist /var/www/html/www/dist
COPY --chown=www-data:www-data --from=composer_prod /app/vendor /var/www/html/vendor

# Pre-compile Symfony cache and assets if applicable
# Warmup cache for production
RUN if [ -f bin/console ]; then \
    php bin/console cache:clear --env=prod --no-debug && \
    php bin/console cache:warmup --env=prod --no-debug; \
    fi

# Ensure correct permissions for Symfony cache and logs (userdata directory)
RUN mkdir -p userdata/cache/prod userdata/tmp/templates_c && \
    chown -R www-data:www-data userdata