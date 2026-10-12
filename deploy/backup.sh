#!/bin/bash
# Dump an instance's database. Prints the path of the dump on success.
# Usage: deploy/backup.sh <instance> [nightly|predeploy]
# Keeps the newest 14 nightly and 10 pre-deploy dumps per instance.
set -euo pipefail
cd "$(dirname "$0")/.."
ENV_DIR=${CARMEN_ENV_DIR:-/srv/carmen}
instance=${1:?usage: backup.sh <instance> [nightly|predeploy]}
label=${2:-nightly}
case $label in nightly) keep=14 ;; predeploy) keep=10 ;; *) echo "unknown label $label" >&2; exit 2 ;; esac

dir="$ENV_DIR/backups/$instance"
mkdir -p "$dir" && chmod 700 "$ENV_DIR/backups" "$dir"

file="$dir/$instance-$label-$(date +%Y%m%d-%H%M%S).sql.gz"
trap 'rm -f "$file.tmp"' EXIT  # no half-written dumps if pg_dump fails
docker compose -p "carmen-$instance" --env-file "$ENV_DIR/$instance.env" -f deploy/compose.yaml \
    exec -T db pg_dump -U carmen carmen | gzip > "$file.tmp"
mv "$file.tmp" "$file"

ls -1t "$dir/$instance-$label-"*.sql.gz | tail -n +$((keep + 1)) | xargs -r rm --
echo "$file"
