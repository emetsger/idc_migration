FROM composer:1.10.17

# TODO GHCR package repository organization 'jhu-sheridan-libraries' and source code org 'jhu-idc' do not align
LABEL org.opencontainers.image.source https://github.com/jhu-idc/idc_migration

RUN apk add --no-cache \
      php7-gd \
      zlib-dev \
      libpng-dev \
      autoconf \
      gcc \
      musl-dev

RUN docker-php-ext-install gd

RUN (echo '' | pecl install xdebug)