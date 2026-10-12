#!/bin/bash
# Nightly database dump of an instance, keeping the last 14. Usage: deploy/backup.sh crm
set -euo pipefail
cd "$(dirname "$0")/.."
ENV_DIR=${CARMEN_ENV_DIR:-/srv/carmen}
instance=${1:-crm}
dir="$ENV_DIR/backups/$instance"
mkdir -p "$dir" && chmod 700 "$ENV_DIR/backups" "$dir"

file="$dir/$instance-$(date +%Y%m%d-%H%M%S).sql.gz"
docker compose -p "carmen-$instance" --env-file "$ENV_DIR/$instance.env" -f deploy/compose.yaml \
    exec -T db pg_dump -U carmen carmen | gzip > "$file.tmp"
mv "$file.tmp" "$file"

ls -1t "$dir"/*.sql.gz | tail -n +15 | xargs -r rm --
