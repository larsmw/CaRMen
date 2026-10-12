#!/bin/sh
# Runs on every container start: JWT keys (once), schema migrations, warm cache.
set -e
cd /app

console() {
    # As www-data so cache files stay writable by php-fpm
    su www-data -s /bin/sh -c "php bin/console $*"
}

chown -R www-data:www-data config/jwt var
if [ ! -f config/jwt/private.pem ]; then
    console lexik:jwt:generate-keypair --no-interaction
fi

console doctrine:migrations:migrate --no-interaction --allow-no-migration
console cache:clear --no-warmup
console cache:warmup

exec "$@"
