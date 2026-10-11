#!/bin/bash
# Empty the dev database so the installer (/installer) runs from scratch.
# Takes a backup first. Usage: ./reset-db.sh [-y]   (-y skips the prompt)
set -euo pipefail
cd "$(dirname "$0")"

DB=${DB:-crm}
BACKUP_DIR=var/backups
BACKUP="$BACKUP_DIR/$DB-$(date +%Y%m%d-%H%M%S).sql.gz"

if [[ "${1:-}" != "-y" ]]; then
    read -rp "This will DELETE all data in the '$DB' database. Continue? [y/N] " answer
    [[ "$answer" =~ ^[yY]$ ]] || { echo "Aborted."; exit 1; }
fi

mkdir -p "$BACKUP_DIR"
docker compose exec -T db pg_dump -U crm "$DB" | gzip > "$BACKUP"
echo "Backup saved to $BACKUP"

# Drop every table (incl. doctrine_migration_versions) but keep the database itself,
# so the running backend doesn't need restarting.
docker compose exec -T db psql -U crm -d "$DB" -q -v ON_ERROR_STOP=1 \
    -c 'SET client_min_messages TO warning; DROP SCHEMA public CASCADE; CREATE SCHEMA public;'

echo "Database '$DB' is empty. Open http://localhost:3001/installer"
echo "Restore with: gunzip -c $BACKUP | docker compose exec -T db psql -U crm -d $DB"
