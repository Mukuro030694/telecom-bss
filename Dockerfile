FROM php:8.2-fpm-alpine

# Системные зависимости
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

# PHP расширения
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    opcache \
    intl \
    zip \
    mbstring

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Рабочая директория
WORKDIR /var/www/html

# Сначала копируем только composer файлы — для кэширования слоёв
COPY composer.json composer.lock ./

# Устанавливаем зависимости
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Копируем остальные файлы
COPY . .

# Запускаем post-install скрипты
RUN composer run-script --no-dev post-install-cmd || true

# Настройка прав
RUN mkdir -p var/cache var/log \
    && chown -R www-data:www-data var/

# Nginx конфиг
COPY docker/nginx.conf /etc/nginx/nginx.conf

# Supervisor конфиг
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Очищаем кэш который мог собраться с dev зависимостями
RUN rm -rf var/cache/*

# Делаем скрипт исполняемым
RUN chmod +x docker/start.sh

EXPOSE 8080

CMD ["docker/start.sh"]