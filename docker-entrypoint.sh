#!/bin/sh
set -e
cd /var/www/html
if [ ! -f vendor/autoload.php ]; then
  composer install --no-interaction --prefer-dist
fi
# Ensure runtime env (e.g. DB_HOST=db from Compose) is used, not cached config
php artisan config:clear 2>/dev/null || true
exec "$@"
