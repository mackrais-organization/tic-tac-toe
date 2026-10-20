FROM php:8.5-fpm-alpine

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /application

RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && mkdir -p /var/lib/php/sessions \
    && chown www-data:www-data /var/lib/php/sessions \
    && chmod 700 /var/lib/php/sessions

COPY .docker/php-fpm/php-ini-overrides.ini /usr/local/etc/php/conf.d/90-application.ini
COPY www/composer.json www/composer.lock ./
COPY www/src ./src
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader
COPY www/config ./config
COPY www/public ./public
