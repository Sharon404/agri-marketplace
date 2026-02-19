FROM php:8.4-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libsodium-dev \
    zip \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql zip bcmath gd pcntl sockets \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install composer binary
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy only composer files to leverage Docker cache for composer install
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --prefer-dist --no-scripts --no-progress --no-interaction && \
    composer clear-cache

# Copy application code
COPY . .

# Create necessary directories if they don't exist
RUN mkdir -p storage/app storage/framework storage/logs bootstrap/cache && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html && \
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Start PHP-FPM (not artisan serve)
CMD ["php-fpm"]
