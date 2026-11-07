#!/bin/sh
set -e
cd /var/www/html

# Nginx: substitueix només ${PORT}
envsubst '${PORT}' < /etc/nginx/templates/default.conf.template > /etc/nginx/conf.d/default.conf

# SQLite (disc efímer a Render gratuït: es recrea a cada desplegament)
DB_FILE="${DB_DATABASE:-/var/www/html/database/database.sqlite}"
mkdir -p "$(dirname "$DB_FILE")"
[ -f "$DB_FILE" ] || touch "$DB_FILE"
chown -R www-data:www-data "$(dirname "$DB_FILE")" storage bootstrap/cache

php artisan package:discover --ansi
php artisan config:clear
php artisan migrate --force
php artisan db:seed --force

# Dades de mostra (activitats, alumnes, reserves...) només si SEED_DEMO=true
if [ "$SEED_DEMO" = "true" ]; then
    php artisan db:seed --class=DemoSeeder --force
fi

php artisan storage:link 2>/dev/null || true
php artisan config:cache
php artisan route:cache
chown -R www-data:www-data storage bootstrap/cache database

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
