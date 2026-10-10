#!/bin/sh
# Entrypoint produksi: menunggu DB, (opsional) migrasi, lalu menghangatkan cache dari lingkungan RUNTIME.
# Migrasi hanya berjalan bila RUN_MIGRATIONS=true (container app saja; queue/scheduler tidak pernah bermigrasi).
set -e

cd /var/www/html

if [ "$1" = "php-fpm" ]; then
    if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
        tries=0
        until php -r 'new PDO("mysql:host=".getenv("DB_HOST").";port=".(getenv("DB_PORT") ?: 3306).";dbname=".getenv("DB_DATABASE"), getenv("DB_USERNAME"), getenv("DB_PASSWORD"));' >/dev/null 2>&1; do
            tries=$((tries + 1))
            [ "$tries" -ge 30 ] && { echo "basis data tidak dapat dijangkau" >&2; exit 1; }
            sleep 2
        done
        php artisan migrate --force --no-interaction
    fi

    php artisan optimize --no-interaction >/dev/null 2>&1 || echo "peringatan: artisan optimize gagal (periksa .env)" >&2
    php artisan filament:optimize --no-interaction >/dev/null 2>&1 || true
    php artisan icons:cache --no-interaction >/dev/null 2>&1 || true
fi

exec "$@"
