FROM php:fpm-alpine3.16
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN apk update
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
    php composer-setup.php && \
    php -r "unlink('composer-setup.php');" && \
    mv "composer.phar" "/usr/local/bin/composer"
RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions sockets mysqli
VOLUME ["/app"]
COPY --chown=www-data:www-data . /app
WORKDIR /app
RUN composer install --no-interaction -a
