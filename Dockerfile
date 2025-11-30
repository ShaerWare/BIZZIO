# Stage 1: Установка зависимостей Composer
FROM composer:2 AS vendor

WORKDIR /app
COPY database/ database/
COPY composer.json composer.json
COPY composer.lock composer.lock
RUN composer install \
    --ignore-platform-reqs \
    --no-interaction \
    --no-plugins \
    --no-scripts \
    --prefer-dist


# Stage 2: Сборка финального образа
FROM php:8.2-fpm-alpine

# Устанавливаем системные зависимости, необходимые для работы приложения и сборки расширений
RUN apk add --no-cache \
    # Постоянные зависимости, необходимые для работы приложения (RUNTIME)
    nginx \
    supervisor \
    libzip \
    postgresql-libs \
    oniguruma \
    libxml2 \
    # >>>>> ИСПРАВЛЕНИЕ: Добавляем runtime-библиотеки для GD сюда <<<<<
    freetype \
    libjpeg-turbo \
    libpng \
    libwebp \
    # Временные зависимости для сборки (BUILD-DEPS)
    && apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    libzip-dev \
    postgresql-dev \
    oniguruma-dev \
    libxml2-dev \
    linux-headers \
    # Dev-пакеты для GD
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    libwebp-dev \
    # Конфигурируем и устанавливаем расширения PHP
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install \
    pdo_pgsql \
    pgsql \
    zip \
    mbstring \
    exif \
    pcntl \
    bcmath \
    sockets \
    opcache \
    gd \
    # Очищаем временные зависимости после сборки
    && apk del .build-deps

# Устанавливаем Composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Копируем код приложения
WORKDIR /var/www
COPY . .

# Копируем установленные зависимости из первого этапа
COPY --from=vendor /app/vendor /var/www/vendor

# Настраиваем права доступа для Laravel
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache && \
    chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Копируем конфигурацию Nginx и Supervisor
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Открываем порт
EXPOSE 80

# Запускаем Supervisor, который будет управлять Nginx и PHP-FPM
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]