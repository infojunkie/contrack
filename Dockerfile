FROM php:8-apache
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli
RUN pear install DB
RUN mkdir /var/www/html/uploads && chown www-data:www-data /var/www/html/uploads