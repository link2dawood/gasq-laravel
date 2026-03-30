# GASQ Laravel — local development only
FROM php:8.2-cli-alpine

RUN apk add --no-cache \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    oniguruma-dev \
    mysql-client \
    icu-dev \
    linux-headers \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        zip \
        exif \
        pcntl \
        bcmath \
        gd \
        intl \
        mbstring \
        fileinfo \
        opcache

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /var/www/html

# Copy app (context is gasq-laravel)
COPY . .

COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh
ENTRYPOINT ["docker-entrypoint.sh"]

EXPOSE 8080

# Run Laravel dev server (local only)
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8080"]
