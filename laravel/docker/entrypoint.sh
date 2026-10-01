#!/bin/sh
set -eu

cd /var/www/html

key_file=storage/app/.docker-app-key
if [ ! -s "$key_file" ]; then
    php -r 'echo "base64:".base64_encode(random_bytes(32));' > "$key_file"
fi
export APP_KEY="$(cat "$key_file")"

chown -R www-data:www-data storage bootstrap/cache

php artisan migrate --force
php artisan db:seed --class=ProductionSeeder --force
php artisan db:seed --class=DockerDemoSeeder --force

exec apache2-foreground
