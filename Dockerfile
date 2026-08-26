FROM php:8.3-cli

WORKDIR /var/www/html

# System packages + PHP extensions needed by Laravel
RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    libpq-dev \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-install \
        bcmath \
        mbstring \
        pdo_pgsql \
        pdo_mysql \
        xml \
        zip \
    && rm -rf /var/lib/apt/lists/*

# Composer 2
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy Composer manifests first for Docker layer caching
COPY composer.json composer.lock ./

# Do not run Laravel scripts during image build:
# Render environment variables and DB do not exist at this stage.
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

# Copy the rest of the Laravel application
COPY . .

# Laravel writable directories
RUN mkdir -p storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Render supplies PORT itself, typically 10000
EXPOSE 10000

CMD sh -c "php artisan config:cache && php artisan route:cache && php artisan serve --host=0.0.0.0 --port=${PORT:-10000}"
