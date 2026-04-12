FROM php:8.2-fpm-alpine

RUN apk add --no-cache \
    nginx \
    postgresql-dev \
    zip \
    unzip \
    git \
    supervisor \
    icu-dev \
    libzip-dev \
    oniguruma-dev

RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    opcache \
    intl \
    zip \
    mbstring

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Переменные окружения для сборки
ENV APP_ENV=prod
ENV APP_DEBUG=0

COPY composer.json composer.lock ./

RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

COPY . .

# Удаляем кэш и дампим env
RUN rm -rf var/cache/* var/log/*
RUN composer dump-env prod || true

RUN mkdir -p var/cache var/log \
    && chown -R www-data:www-data var/

COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

RUN chmod +x docker/start.sh

EXPOSE 8080

CMD ["docker/start.sh"]