FROM php:8.3-apache

RUN apt update && apt install -y \
    libzip-dev \
    unzip \
    git \
    && docker-php-ext-install zip opcache \
    && a2enmod rewrite headers expires deflate

RUN echo "ServerName localhost" > /etc/apache2/conf-available/servername.conf \
    && a2enconf servername

RUN echo "\
<IfModule mod_headers.c>\n\
    Header always set X-Content-Type-Options \"nosniff\"\n\
    Header always set X-Frame-Options \"DENY\"\n\
    Header always set X-XSS-Protection \"1; mode=block\"\n\
    Header always set Referrer-Policy \"strict-origin-when-cross-origin\"\n\
</IfModule>\n\
" > /etc/apache2/conf-available/security.conf \
    && a2enconf security

RUN echo "\
<IfModule mod_expires.c>\n\
    ExpiresActive On\n\
    ExpiresByType text/css \"access plus 1 month\"\n\
    ExpiresByType application/javascript \"access plus 1 month\"\n\
</IfModule>\n\
\n\
<IfModule mod_deflate.c>\n\
    AddOutputFilterByType DEFLATE text/plain text/html text/xml text/css application/javascript\n\
</IfModule>\n\
" > /etc/apache2/conf-available/cache.conf \
    && a2enconf cache

RUN echo "\
opcache.enable=1\n\
opcache.memory_consumption=128\n\
opcache.interned_strings_buffer=8\n\
opcache.max_accelerated_files=10000\n\
opcache.validate_timestamps=0\n\
opcache.revalidate_freq=0\n\
" > /usr/local/etc/php/conf.d/opcache.ini

WORKDIR /var/webapp/api

COPY ./api /var/webapp/api

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN COMPOSER_ALLOW_SUPERUSER=1 composer install \
    --no-dev \
    --optimize-autoloader \
    --classmap-authoritative

ENV APACHE_DOCUMENT_ROOT=/var/webapp/api/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf \
    /etc/apache2/apache2.conf

RUN echo "\
<Directory /var/webapp/api/public>\n\
    AllowOverride None\n\
    Require all granted\n\
    Options -Indexes +FollowSymLinks\n\
</Directory>\n\
" > /etc/apache2/conf-available/app.conf \
    && a2enconf app

RUN chown -R www-data:www-data /var/webapp/api \
    && find /var/webapp/api -type d -exec chmod 755 {} \; \
    && find /var/webapp/api -type f -exec chmod 644 {} \;

EXPOSE 80

CMD ["apache2-foreground"]