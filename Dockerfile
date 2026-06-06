FROM composer:2 as builder
WORKDIR /app
COPY backend/php/composer.json backend/php/composer.lock* /app/
RUN composer install --no-dev --optimize-autoloader

FROM php:8.2-cli
RUN apt-get update && apt-get install -y libzip-dev unzip zip && docker-php-ext-install pdo_mysql
WORKDIR /var/www/html
COPY --from=builder /app/vendor /var/www/html/vendor
COPY . /var/www/html
RUN mkdir -p /var/www/html/backend/php/uploads /var/www/html/backend/php/logs && chown -R www-data:www-data /var/www/html/backend/php/uploads /var/www/html/backend/php/logs
ENV PORT=10000
EXPOSE 10000
CMD ["sh","-lc","php -S 0.0.0.0:${PORT} router.php"]
