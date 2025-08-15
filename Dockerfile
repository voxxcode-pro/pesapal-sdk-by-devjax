FROM php:8.2-apache

# Install dependencies
RUN apt-get update && apt-get install -y \
    unzip git curl libcurl4-openssl-dev libzip-dev libsqlite3-dev sqlite3 zip \
    && docker-php-ext-install pdo pdo_sqlite curl zip

# Enable Apache Rewrite
RUN a2enmod rewrite

# Working dir
WORKDIR /var/www/html

# Copy all code
COPY . /var/www/html

# Install Composer globally
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install dependencies
RUN if [ -f "composer.json" ]; then composer install --no-interaction --prefer-dist; fi

# Set permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
