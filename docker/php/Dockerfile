FROM php:8.3-cli-alpine

RUN set -ex && \
        apk --no-cache --allow-untrusted add --update \
        g++ \
        gcc \
        zlib \
        linux-headers \
        make \
        icu-dev \
        git \
        bash \
        zip \
        autoconf \
        libzip-dev \
        zlib-dev \
        libsodium \
        freetype-dev \
        libjpeg-turbo-dev \
        libpng-dev \
        libsodium-dev && \
      docker-php-source extract && \
      docker-php-ext-install bcmath intl pcntl && \
      docker-php-ext-configure sodium && \
      docker-php-ext-configure pcntl --enable-pcntl && \
      docker-php-ext-configure zip && \
      rm -rf /var/cache/apk/* && \
      docker-php-source delete

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
   && chmod +x /usr/local/bin/composer \
   && composer clear-cache

WORKDIR /var/www
