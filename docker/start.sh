#!/bin/sh
set -e
cd /var/www/html

# Nginx: substitueix només ${PORT}
envsubst '${PORT}' < /etc/nginx/templates/default.conf.template > /etc/nginx/conf.d/default.conf

chown -R www-data:www-data storage bootstrap/cache

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs
php artisan package:discover --ansi
php artisan config:clear
php artisan migrate --force
# Els seeders NO s'executen aquí: es llancen manualment una sola vegada
# (php artisan db:seed --class=BaseSeeder --force) contra la base de dades de producció.

php artisan storage:link 2>/dev/null || true
php artisan config:cache
php artisan route:cache
chown -R www-data:www-data storage bootstrap/cache

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
