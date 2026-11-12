FROM php:8.4-apache-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends libicu-dev libonig-dev libzip-dev unzip git \
    && docker-php-ext-install -j"$(nproc)" bcmath intl mbstring pdo_mysql zip opcache \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY . .
RUN composer install --no-scripts --no-interaction --prefer-dist \
    && a2enmod rewrite \
    && sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf
COPY docker/apache-laravel.conf /etc/apache2/conf-available/laravel.conf
COPY docker/entrypoint.sh /usr/local/bin/fa-entrypoint
RUN chmod +x /usr/local/bin/fa-entrypoint && a2enconf laravel
ENTRYPOINT ["fa-entrypoint"]
