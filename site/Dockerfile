FROM php:7-apache
 
RUN apt-get update && \
    apt-get install -y --no-install-recommends git zip zlib1g-dev libldap2-dev && \
	docker-php-ext-configure ldap --with-libdir=lib/x86_64-linux-gnu/ && \
	docker-php-ext-install pdo_mysql mysqli zip ldap calendar
 
# Enable Apache mod_rewrite
RUN a2enmod rewrite
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf
RUN sed -i 's%/var/www/html%/var/www/html/public%g' /etc/apache2/sites-enabled/000-default.conf
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
