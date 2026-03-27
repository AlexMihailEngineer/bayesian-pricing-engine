FROM php:8.4-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libpq-dev \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions required for Laravel, DDD, and Math
RUN docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath gd zip

# Install Redis extension via PECL
RUN pecl install redis \
    && docker-php-ext-enable redis

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# We set user to www-data for security and permission alignment
RUN chown -R www-data:www-data /var/www

USER www-data