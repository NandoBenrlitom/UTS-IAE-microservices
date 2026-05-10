# Image PHP 8.2 CLI
FROM php:8.2-cli

# Dependensi sistem
RUN apt-get update -y && apt-get install -y \
    ca-certificates \
    curl \
    libmcrypt-dev \
    git \
    unzip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    default-mysql-client \
 && rm -rf /var/lib/apt/lists/*

# Ekstensi PHP - pakai install-php-extensions (lebih reliable dari pecl)
ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_mysql mbstring exif pcntl bcmath gd redis

# Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /app
COPY . /app

RUN composer install --no-dev --optimize-autoloader --no-interaction
RUN mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache

EXPOSE 8000

# Tunggu MySQL siap, jalankan migrate + seed (idempoten), lalu start server.
CMD ["sh", "-c", "until mysqladmin --skip-ssl ping -h \"$DB_HOST\" -u\"$DB_USERNAME\" -p\"$DB_PASSWORD\" --silent; do echo waiting for mysql; sleep 2; done && php artisan migrate --force && php artisan db:seed --force && php artisan serve --host=0.0.0.0 --port=8000"]
