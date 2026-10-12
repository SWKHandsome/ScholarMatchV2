#!/bin/sh
set -eu
cd /home/site/wwwroot
test -f public/index.php
test -f vendor/autoload.php
test -n "${APP_KEY:-}"
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chmod -R ug+rw storage bootstrap/cache
cp deploy/nginx.conf /etc/nginx/sites-available/default
service nginx reload
php artisan config:cache --no-interaction
php artisan route:cache --no-interaction
php artisan view:cache --no-interaction
