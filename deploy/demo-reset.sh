#!/bin/bash
# Wipe the public demo and reload the shared demo login + demo data. Run nightly from cron.
set -euo pipefail
cd "$(dirname "$0")/.."
ENV_DIR=${CARMEN_ENV_DIR:-/srv/carmen}

docker compose -p carmen-demo --env-file "$ENV_DIR/demo.env" -f deploy/compose.yaml exec -T -u www-data app sh -c '
    php bin/console doctrine:schema:drop --full-database --force --no-interaction -q &&
    php bin/console doctrine:migrations:migrate --no-interaction -q &&
    php bin/console app:demo:load --email="$DEMO_ACCOUNT_EMAIL" --password="$DEMO_ACCOUNT_PASSWORD"'
