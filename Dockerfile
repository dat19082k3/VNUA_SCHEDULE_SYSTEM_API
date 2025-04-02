# ----------------------------------
# Stage 1: Base PHP-FPM Image
# ----------------------------------
    FROM php:8.2-fpm-alpine AS base

    # Cài các extension PHP cần thiết cho Laravel API
    RUN apk add --no-cache \
        zip unzip curl bash mysql-client libpng libzip-dev libjpeg-turbo-dev freetype-dev \
        && docker-php-ext-configure gd --with-freetype --with-jpeg \
        && docker-php-ext-install pdo pdo_mysql gd zip
    
    # Tạo thư mục làm việc
    WORKDIR /var/www/html
    
    # Cấp quyền cho storage và bootstrap/cache (Laravel yêu cầu)
    RUN mkdir -p storage/logs bootstrap/cache \
        && chown -R www-data:www-data storage bootstrap/cache
    
    # ----------------------------------
    # Stage 2: Composer Dependencies
    # ----------------------------------
    FROM composer:2 AS composer
    
    WORKDIR /app
    
    # Copy composer files trước để tận dụng cache layer
    COPY composer.json composer.lock ./
    
    # Cài đặt dependency
    RUN composer install --no-dev --prefer-dist --no-scripts --no-interaction --optimize-autoloader
    
    # ----------------------------------
    # Stage 3: Final Production Image
    # ----------------------------------
    FROM base AS app
    
    WORKDIR /var/www/html
    
    # Copy toàn bộ mã nguồn (bỏ những file ignore qua .dockerignore)
    COPY . .
    
    # Copy vendor đã cài từ stage composer
    COPY --from=composer /app/vendor ./vendor
    
    # Cache config & route (tối ưu Laravel)
    RUN php artisan config:cache && php artisan route:cache
    
    # Cấp quyền cho storage & cache
    RUN chown -R www-data:www-data storage bootstrap/cache
    
    # Expose cổng PHP-FPM
    EXPOSE 9000
    
    # Chạy PHP-FPM
    CMD ["php-fpm"]
    