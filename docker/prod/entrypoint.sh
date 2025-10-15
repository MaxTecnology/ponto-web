#!/usr/bin/env sh
set -e

cd /var/www/html

mkdir -p storage/framework/{cache,sessions,testing,views}
mkdir -p storage/logs
touch storage/logs/laravel.log
chown -R www-data:www-data storage bootstrap/cache

if [ ! -L public/storage ]; then
    ln -sfn /var/www/html/storage/app/public /var/www/html/public/storage || true
fi

run_www() {
    su-exec www-data "$@"
}

if [ -n "${APP_KEY}" ] && [ "${APP_KEY}" != "base64:" ] && [ "${APP_CACHE_CONFIG:-true}" != "false" ]; then
    run_www php artisan config:clear >/dev/null 2>&1 || true
    run_www php artisan cache:clear >/dev/null 2>&1 || true
    run_www php artisan config:cache >/dev/null 2>&1 || true
    run_www php artisan route:cache >/dev/null 2>&1 || true
    run_www php artisan view:cache >/dev/null 2>&1 || true
fi

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    run_www php artisan migrate --force --isolated || true
fi

exec su-exec www-data "$@"
