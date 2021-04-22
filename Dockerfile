FROM composer:1.10.17

RUN apk add --no-cache \
      php7-gd \
      zlib-dev \
      libpng-dev \
      autoconf \
      gcc \
      musl-dev

RUN docker-php-ext-install gd

RUN (echo '' | pecl install xdebug)