FROM php:8.2-fpm-alpine

# Системные зависимости
RUN apk update && apk add --no-cache \
    nginx \
    postgresql-dev \
    zip \
    unzip \
    git \
    supervisor

# PHP расширения
RUN docker-php-ext-install pdo pdo_pgsql opcache

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Рабочая директория
WORKDIR /var/www/html

# Копируем файлы
COPY . .

# Устанавливаем зависимости
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Настройка прав
RUN chown -R www-data:www-data /var/www/html/var

# Nginx конфиг
COPY docker/nginx.conf /etc/nginx/nginx.conf

# Supervisor конфиг
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

RUN chmod +x docker/start.sh

EXPOSE 8080

CMD ["docker/start.sh"]