FROM php:8.3-fpm-alpine AS base

# System dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    oniguruma-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    mysql-client \
    postgresql-dev \
    linux-headers \
    nginx \
    supervisor \
    $PHPIZE_DEPS \
    && docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    pdo_pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copy composer files first for Docker layer caching
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress

# Copy application
COPY . .

# Set permissions
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache \
    && chmod +x /var/www/docker-start.sh

# Nginx config
COPY nginx/render.conf /etc/nginx/http.d/default.conf

# Supervisor config — Alpine path
COPY supervisord.conf /etc/supervisord.conf

EXPOSE 10000

CMD ["/var/www/docker-start.sh"]
