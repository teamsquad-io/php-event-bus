FROM php:fpm-alpine3.16
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN apk update
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
    php composer-setup.php && \
    php -r "unlink('composer-setup.php');" && \
    mv "composer.phar" "/usr/local/bin/composer"
RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions sockets mysqli xdebug


RUN echo "[xdebug]" > /usr/local/etc/php/conf.d/docker-php-ext-xdebug-conf.ini && \
    echo "xdebug.mode=develop,debug" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug-conf.ini && \
    echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug-conf.ini && \
    echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug-conf.ini && \
    echo "xdebug.client_port=9003" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug-conf.ini && \
    echo "xdebug.discover_client_host=1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug-conf.ini && \
    echo "xdebug.idekey=PHPSTORM" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug-conf.ini

VOLUME ["/app"]
COPY --chown=www-data:www-data . /app
WORKDIR /app
RUN composer install --no-interaction -a
