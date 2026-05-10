# Menggunakan image PHP 8.2 berbasis CLI
FROM php:8.2-cli

# Instalasi dependensi sistem yang dibutuhkan Laravel
RUN apt-get update -y && apt-get install -y \
    ca-certificates \
    curl \
    libmcrypt-dev \
    git \
    unzip \
    libpng-dev \
    libonig-dev \
    libxml2-dev

# Instalasi ekstensi PHP untuk MySQL dan Redis
RUN docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd
RUN pecl install redis && docker-php-ext-enable redis

# Instalasi Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set working directory di dalam container
WORKDIR /app

# Copy semua file proyek ke dalam container
COPY . /app

# Jalankan perintah composer install
RUN composer install --no-scripts --no-autoloader && composer dump-autoloader

# Menjalankan server Laravel
CMD php artisan serve --host=0.0.0.0 --port=8000
