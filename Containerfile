# Build:
# podman build -t my-app .

# Run:
# podman run --rm -p 8080:80 -v "$(pwd)":/var/www/html -v /var/www/html/writable my-app

# Shell:
# podman run -it --rm -p 8080:80 -v "$(pwd)":/var/www/html -v /var/www/html/writable my-app bash

FROM docker.io/library/php:8.5.10-apache-trixie

WORKDIR /var/www/html

# ------------ #
# Dependencies #
# ------------ #
RUN apt-get update && apt-get install -y \
    libicu-dev \
    libpq-dev \
    zip \
    unzip \
    git \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl pdo pdo_pgsql pgsql \
    && a2enmod rewrite

# -------- #
# Composer #
# -------- #
COPY --from=docker.io/library/composer:2.10.3 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./

RUN composer install \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader

# ----------- #
# Application #
# ----------- #
COPY vhost.conf /etc/apache2/sites-available/000-default.conf

COPY . .

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

