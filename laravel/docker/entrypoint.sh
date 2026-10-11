#!/bin/sh
set -eu

cd /var/www/html

key_file=storage/app/.docker-app-key
if [ ! -s "$key_file" ]; then
    php -r 'echo "base64:".base64_encode(random_bytes(32));' > "$key_file"
fi
export APP_KEY="$(cat "$key_file")"

chown -R www-data:www-data storage bootstrap/cache

# Datenmodell aus datenbank/ anlegen und beim ersten Start die Testdaten laden.
# Sind schon Benutzer da, lässt der Seeder die Daten in Ruhe.
php artisan migrate --force
php artisan db:seed --force

exec apache2-foreground
