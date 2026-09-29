#!/usr/bin/env bash
set -Eeuo pipefail
umask 0027
cd /var/www/html

if [[ "${APP_DEPLOYMENT:-render}" == "compose" ]]; then
    php docker/check-compose-runtime.php
else
    php docker/check-runtime.php

# Render mounts secret files here at runtime; never copy them into image layers.
ca_source="${MYSQL_SSL_CA_SOURCE:-/etc/secrets/ca.pem}"
php docker/check-ca.php "$ca_source"
install -d -o root -g www-data -m 0750 /var/www/private
install -o root -g www-data -m 0640 "$ca_source" /var/www/private/ca.pem
export MYSQL_ATTR_SSL_CA=/var/www/private/ca.pem
gosu www-data php docker/check-ca.php "$MYSQL_ATTR_SSL_CA"
fi

mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions \
    storage/framework/views storage/logs bootstrap/cache public/images/products public/images/videos public/images/reviews
chown -R www-data:www-data storage bootstrap/cache public/images/products public/images/videos public/images/reviews
chmod -R ug+rwX,o-rwx storage bootstrap/cache
# Public uploads must be readable through nginx's /storage symlink.
chmod -R u=rwX,g=rX,o= storage/app/public

# Preserve the canonical external port even with Debian's patched FastCGI defaults.
export APP_HTTP_HOST="$(php -r '$u = parse_url(getenv("APP_URL")); echo $u["host"].(isset($u["port"]) ? ":".$u["port"] : "");')"
# Only replace app values; preserve nginx variables such as $uri.
envsubst '${PORT} ${APP_HTTP_HOST}' < /etc/nginx/nginx.conf.template > /etc/nginx/nginx.conf
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

if [[ "${LOCAL_DEMO_SEED:-false}" == "true" ]]; then
    gosu www-data php artisan db:seed --class=LocalDemoSeeder --force --no-interaction
fi

# public/ remains root-owned; create the link before starting unprivileged workers.
php artisan storage:link --no-interaction
gosu www-data php artisan config:cache --no-interaction
gosu www-data php artisan view:cache --no-interaction

# Intentionally do not route:cache: this application still contains closure routes.
exec /usr/local/bin/app-supervise
