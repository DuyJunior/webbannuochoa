#!/usr/bin/env bash
set -Eeuo pipefail
umask 0027
cd /var/www/html

php docker/check-runtime.php

# Render mounts secret files here at runtime; never copy them into image layers.
ca_source="${MYSQL_SSL_CA_SOURCE:-/etc/secrets/ca.pem}"
php docker/check-ca.php "$ca_source"
install -d -o root -g www-data -m 0750 /var/www/private
install -o root -g www-data -m 0640 "$ca_source" /var/www/private/ca.pem
export MYSQL_ATTR_SSL_CA=/var/www/private/ca.pem
gosu www-data php docker/check-ca.php "$MYSQL_ATTR_SSL_CA"

mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions \
    storage/framework/views storage/logs bootstrap/cache public/images/products public/images/videos
chown -R www-data:www-data storage bootstrap/cache public/images/products public/images/videos
chmod -R ug+rwX,o-rwx storage bootstrap/cache

# Only replace PORT; preserve nginx variables such as $uri and $document_root.
envsubst '${PORT}' < /etc/nginx/nginx.conf.template > /etc/nginx/nginx.conf
nginx -t
php-fpm -t

gosu www-data php artisan config:clear --no-interaction
gosu www-data php artisan package:discover --no-interaction
gosu www-data php docker/check-db.php

if [[ "$RUN_MIGRATIONS" == "true" ]]; then
    gosu www-data php artisan migrate --force --no-interaction
fi

if [[ "$RUN_SEEDERS" == "true" ]]; then
    gosu www-data php artisan db:seed --class=ProductionSeeder --force --no-interaction
fi

gosu www-data php artisan storage:link --no-interaction
gosu www-data php artisan config:cache --no-interaction
gosu www-data php artisan view:cache --no-interaction

# Intentionally do not route:cache: this application still contains closure routes.
exec /usr/local/bin/app-supervise
