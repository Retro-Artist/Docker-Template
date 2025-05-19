FROM php:8.3-apache

# Install system dependencies and PHP extensions in a single layer for better caching
RUN apt-get update && apt-get install -y --no-install-recommends \
    curl \
    git \
    libonig-dev \
    libpng-dev \
    libxml2-dev \
    libzip-dev \
    unzip \
    zip \
    && docker-php-ext-install \
    bcmath \
    exif \
    gd \
    mbstring \
    pcntl \
    pdo_mysql \
    zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer with cache mount for faster rebuilds
RUN --mount=type=cache,target=/tmp/cache \
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Enable Apache rewrite module
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Configure Apache
COPY apache-config.conf /etc/apache2/sites-available/000-default.conf

# Restart Apache
CMD ["apache2-foreground"]
