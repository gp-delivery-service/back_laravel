FROM php:8.2-fpm

# Установка расширений
RUN apt-get update && apt-get install -y \
    git curl unzip zip libzip-dev libpng-dev libonig-dev libxml2-dev \
    && docker-php-ext-install pdo_mysql mbstring zip exif pcntl

# Установка Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Создание рабочей директории
WORKDIR /var/www

# Копируем проект внутрь образа
COPY . .

# Установка зависимостей Laravel
RUN composer install --no-dev --optimize-autoloader

# Генерация ключа, создание storage-ссылки и миграции
RUN php artisan key:generate \
 && php artisan storage:link \
 && php artisan config:cache

# Разрешения
RUN chown -R www-data:www-data /var/www && chmod -R 755 /var/www
