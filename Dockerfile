
FROM php:8.5-apache

# Install system libraries required by PHP extensions
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    libcurl4-openssl-dev \
    libpq-dev \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j2 \
        pdo_pgsql \
        bcmath \
        mbstring \
        exif \
        gd \
        zip \
        intl \
        opcache \
        pcntl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set application directory
WORKDIR /var/www/html

# Install production dependencies
COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

# Copy Laravel application
COPY . .

# Run Laravel package discovery
RUN composer dump-autoload \
    --no-dev \
    --optimize \
    --no-interaction

# Configure Apache to serve Laravel's public directory
RUN sed -ri 's!/var/www/html!/var/www/html/public!g' \
    /etc/apache2/sites-available/000-default.conf \
    && printf '\n<Directory /var/www/html/public>\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>\n' >> /etc/apache2/apache2.conf \
    && a2enmod rewrite headers

# Set Laravel permissions
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Production PHP settings
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Render supplies the PORT environment variable
EXPOSE 10000

CMD ["sh", "-c", "sed -i \"s/Listen 80/Listen ${PORT:-10000}/\" /etc/apache2/ports.conf && sed -i \"s/:80>/:${PORT:-10000}>/\" /etc/apache2/sites-available/000-default.conf && apache2-foreground"]
