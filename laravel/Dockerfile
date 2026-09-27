FROM php:8.4-apache-bookworm AS app

RUN apt-get update \
    && apt-get install -y --no-install-recommends libicu-dev libonig-dev libzip-dev unzip \
    && docker-php-ext-install -j"$(nproc)" bcmath intl mbstring pdo_mysql zip opcache \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --optimize-autoloader \
    && php artisan package:discover --ansi \
    && a2enmod rewrite \
    && sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

FROM node:22-bookworm AS assets
WORKDIR /app
COPY --from=app /var/www/html /app
RUN npm ci --no-audit --no-fund && npm run build

FROM app
COPY --from=assets /app/public/build /var/www/html/public/build
COPY docker/apache-laravel.conf /etc/apache2/conf-available/laravel.conf
COPY docker/entrypoint.sh /usr/local/bin/fa-entrypoint
RUN chmod +x /usr/local/bin/fa-entrypoint \
    && a2enconf laravel \
    && mkdir -p storage/app storage/framework/cache storage/framework/sessions storage/framework/views storage/logs \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80
ENTRYPOINT ["fa-entrypoint"]
