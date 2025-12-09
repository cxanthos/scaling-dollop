FROM php:8.4-apache

ENV APACHE_DOCUMENT_ROOT /var/www/html/be/public

# Copy Composer from the official Composer image
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

RUN apt-get update && apt-get install -y zip

RUN docker-php-ext-install pdo_mysql

RUN a2enmod rewrite && \
  sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf && \
  sed -ri -e 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf
