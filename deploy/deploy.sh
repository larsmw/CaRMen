#!/bin/bash
# (Re)build and start CaRMen instances from the current checkout.
# Usage: deploy/deploy.sh [crm|demo ...]   (default: all instances with an env file)
set -euo pipefail
cd "$(dirname "$0")/.."
ENV_DIR=${CARMEN_ENV_DIR:-/srv/carmen}

instances=("$@")
if [ ${#instances[@]} -eq 0 ]; then
    for f in "$ENV_DIR"/*.env; do instances+=("$(basename "$f" .env)"); done
fi

for i in "${instances[@]}"; do
    echo "== deploying $i"
    docker compose -p "carmen-$i" --env-file "$ENV_DIR/$i.env" -f deploy/compose.yaml up -d --build --remove-orphans
done
docker image prune -f >/dev/null
