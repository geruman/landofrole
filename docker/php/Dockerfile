FROM php:8.3-apache

# Extensiones necesarias
RUN apt-get update && apt-get install -y \
    libpng-dev libonig-dev libzip-dev zip unzip git \
 && docker-php-ext-install pdo pdo_mysql gd \
 && rm -rf /var/lib/apt/lists/*

# Xdebug (opcional)
RUN pecl install xdebug \
 && docker-php-ext-enable xdebug

# Apache
RUN a2enmod rewrite

WORKDIR /var/www/html

