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

# Default command will run artisan serve (dev). For production use PHP-FPM + Nginx.
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
