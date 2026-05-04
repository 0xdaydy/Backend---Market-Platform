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
# --no-scripts avoids needing artisan before full app copy
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress --no-scripts

# Copy application
COPY . .

# Rebuild autoload with scripts now that artisan is available
RUN composer dump-autoload --no-dev --optimize --no-interaction

# Set permissions
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache \
    && chmod +x /var/www/docker-start.sh

# Ensure PHP-FPM workers can access env vars and log errors
RUN echo "catch_workers_output = yes" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "clear_env = no" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "php_admin_flag[log_errors] = on" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "php_admin_value[error_log] = /proc/self/fd/2" >> /usr/local/etc/php-fpm.d/www.conf

# Nginx config
COPY nginx/render.conf /etc/nginx/http.d/default.conf

# Supervisor config — Alpine path
COPY supervisord.conf /etc/supervisord.conf

EXPOSE 10000

CMD ["/var/www/docker-start.sh"]
