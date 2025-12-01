FROM php:8.3-fpm-alpine

# пакеты и расширения
RUN apk add --no-cache bash curl git unzip rsync postgresql-dev \
    libpng-dev libjpeg-turbo-dev freetype-dev libzip-dev \
 && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
 && apk add --no-cache pcre-dev \
 && pecl install redis \
 && docker-php-ext-enable redis \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j$(nproc) pdo pdo_pgsql gd zip \
 && apk del .build-deps

# composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# каталоги
RUN mkdir -p /var/www/html /opt/laravel-patches
WORKDIR /var/www/html

# entrypoint
COPY entrypoint.sh /usr/local/bin/php_entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/php_entrypoint.sh && \
    chmod +x /usr/local/bin/php_entrypoint.sh

CMD ["bash", "/usr/local/bin/php_entrypoint.sh"]
