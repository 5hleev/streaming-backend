FROM php:8.3-fpm
RUN apt-get update \
 && apt-get install -y --no-install-recommends ffmpeg libpq-dev unzip git \
 && docker-php-ext-install pdo_pgsql \
 && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY uploads.ini /usr/local/etc/php/conf.d/uploads.ini
WORKDIR /var/www
