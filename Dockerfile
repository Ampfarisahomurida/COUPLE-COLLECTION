FROM composer:2 as builder
WORKDIR /app
COPY backend/php/composer.json /app/composer.json
RUN composer install --no-dev --optimize-autoloader

FROM php:8.2-fpm
RUN docker-php-ext-install pdo_mysql
WORKDIR /var/www/html
COPY . /var/www/html
COPY --from=builder /app/vendor /var/www/html/backend/php/vendor
RUN mkdir -p /var/www/html/backend/php/uploads /var/www/html/backend/php/logs && chown -R www-data:www-data /var/www/html/backend/php/uploads /var/www/html/backend/php/logs
EXPOSE 9000
