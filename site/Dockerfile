FROM php:7.0-apache
 
RUN docker-php-ext-install pdo mysqli
RUN apt-get update && \
    apt-get install -y --no-install-recommends git zip
 
# Enable Apache mod_rewrite
RUN a2enmod rewrite
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf
RUN sed -i 's%/var/www/html%/var/www/html/public%g' /etc/apache2/sites-enabled/000-default.conf
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
