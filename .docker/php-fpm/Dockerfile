FROM phpdockerio/php:8.2-fpm
WORKDIR "/application"

ENV PHP_V=8.2
ENV TERM='xterm-256color'

# Fix debconf warnings upon build
ARG DEBIAN_FRONTEND=noninteractive

# Install selected extensions and other stuff
RUN apt-get update && apt-get -y dist-upgrade\
   && apt-get -y install apt-utils locales git mc\
   && apt-get clean; rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*
RUN apt-get update\
    && apt-get -y --no-install-recommends install php${PHP_V}-bcmath php${PHP_V}-json php${PHP_V}-mbstring php-xdebug\
    && apt-get clean; rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*
RUN curl -sSk https://getcomposer.org/installer | php -- --disable-tls && \
   mv composer.phar /usr/local/bin/composer

RUN apt-get update && apt-get -y dist-upgrade\
   && apt-get clean; rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*
