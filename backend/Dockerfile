FROM composer:2.6 AS vendor
WORKDIR /app
COPY . .
RUN composer install --no-dev --prefer-dist --optimize-autoloader --ignore-platform-reqs
FROM php:8.3-fpm
RUN apt-get update && apt-get install -y \
    git unzip libpng-dev libonig-dev libxml2-dev zip curl \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

WORKDIR /var/www
COPY . /var/www/
COPY --from=vendor /app/vendor /var/www/vendor
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
EXPOSE 9000
CMD ["php-fpm"]