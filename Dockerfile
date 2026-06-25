FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libssl-dev \
    unzip \
    git \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo_mysql

RUN pecl install redis && docker-php-ext-enable redis

RUN pecl install mongodb && docker-php-ext-enable mongodb

RUN a2enmod rewrite

COPY . /var/www/html/

WORKDIR /var/www/html/

EXPOSE 80
