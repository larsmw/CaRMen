#!/bin/bash
# Deploy, roll back and inspect CaRMen instances. See deploy/README.md.
#
#   deploy.sh deploy   <instance> [sha]   deploy the newest main commit CI published images for
#                                         (or that commit); switches this checkout to it first
#   deploy.sh build    <instance>         build images from this checkout and switch to them
#   deploy.sh rollback <instance>         switch back to the previously deployed tag
#   deploy.sh status                      what runs where, and whether a newer version is published
#
# A deploy: pull images -> back up the database (real instances) -> switch -> health check.
# If the health check fails it switches back to the previous tag automatically, first
# restoring the pre-deploy backup if the failed version changed the schema.
set -euo pipefail
cd "$(dirname "$0")/.."
ENV_DIR=${CARMEN_ENV_DIR:-/srv/carmen}
STATE_DIR=$ENV_DIR/state
HEALTH_TIMEOUT=${HEALTH_TIMEOUT:-120}
mkdir -p "$STATE_DIR"

log()    { echo "[$(date '+%F %T')] $*"; }
record() { echo "$(date -Is) $1 $2 $3" >> "$ENV_DIR/deploy.log"; }
envval() { grep -E "^$2=" "$ENV_DIR/$1.env" | tail -1 | cut -d= -f2- || true; }
compose() { local i=$1; shift; docker compose -p "carmen-$i" --env-file "$ENV_DIR/$i.env" -f deploy/compose.yaml "$@"; }
current_tag()  { cat "$STATE_DIR/$1.current" 2>/dev/null || true; }
previous_tag() { cat "$STATE_DIR/$1.previous" 2>/dev/null || true; }
is_demo() { [[ -n $(envval "$1" DEMO_ACCOUNT_EMAIL) ]]; }

images() {  # instance tag -> the app and web image refs for that tag
    local web; web=$(envval "$1" WEB_IMAGE)
    echo "ghcr.io/larsmw/carmen-app:$2 ${web:-ghcr.io/larsmw/carmen-web}:$2"
}

migration_version() {
    compose "$1" exec -T db psql -U carmen -d carmen -tAc \
        "SELECT COALESCE(MAX(version), '') FROM doctrine_migration_versions" 2>/dev/null || echo none
}

is_installed() {  # instance: API answers and reports users (false if the DB is unreachable)
    curl -fsS "http://127.0.0.1:$(envval "$1" HTTP_PORT)/api/install/status" 2>/dev/null | grep -q '"installed":true'
}

healthy() {  # instance [must_be_installed]: front page and API answer
    local port deadline want=${2:-true} pattern='"installed":'
    port=$(envval "$1" HTTP_PORT)
    [[ $want == true ]] && pattern='"installed":true'
    deadline=$((SECONDS + HEALTH_TIMEOUT))
    while (( SECONDS < deadline )); do
        if curl -fsS -o /dev/null "http://127.0.0.1:$port/" 2>/dev/null \
            && curl -fsS "http://127.0.0.1:$port/api/install/status" 2>/dev/null | grep -q "$pattern"; then
            return 0
        fi
        sleep 3
    done
    return 1
}

switch_to() {  # instance tag [must_be_installed]
    IMAGE_TAG=$2 compose "$1" up -d --no-build --remove-orphans
    healthy "$1" "${3:-true}"
}

restore_db() {  # instance dump
    compose "$1" stop app
    compose "$1" exec -T db psql -U carmen -d carmen -q -v ON_ERROR_STOP=1 \
        -c 'SET client_min_messages TO warning; DROP SCHEMA public CASCADE; CREATE SCHEMA public;'
    gunzip -c "$2" | compose "$1" exec -T db psql -U carmen -d carmen -q -v ON_ERROR_STOP=1 >/dev/null
}

do_deploy() {  # instance tag
    local i=$1 tag=$2 prev backup="" mig_before was_installed=false
    prev=$(current_tag "$i")
    # A fresh instance has no users yet; an installed one must still report them afterwards
    is_installed "$i" && was_installed=true
    log "deploying $i: ${prev:-<none>} -> $tag"

    for img in $(images "$i" "$tag"); do
        docker image inspect "$img" >/dev/null 2>&1 || docker pull -q "$img"
    done

    if ! is_demo "$i" && [[ -n $(compose "$i" ps -q --status running db) ]]; then
        backup=$(deploy/backup.sh "$i" predeploy)
        log "pre-deploy backup: $backup"
    fi
    mig_before=$(migration_version "$i")

    if switch_to "$i" "$tag" "$was_installed"; then
        [[ -n $prev && $prev != "$tag" ]] && echo "$prev" > "$STATE_DIR/$i.previous"
        echo "$tag" > "$STATE_DIR/$i.current"
        record "$i" "$tag" ok
        log "OK: $i is running $tag"
        return 0
    fi

    log "FAILED: $i did not become healthy on $tag within ${HEALTH_TIMEOUT}s"
    compose "$i" logs --tail 40 app >&2 || true
    if [[ -z $prev ]]; then
        record "$i" "$tag" "failed (no previous version to roll back to)"
        return 1
    fi

    local schema_changed=false
    [[ $(migration_version "$i") != "$mig_before" ]] && schema_changed=true
    if $schema_changed && [[ -n $backup ]]; then
        log "schema changed during the failed deploy; restoring $backup"
        restore_db "$i" "$backup"
    fi

    log "rolling back $i to $prev"
    if switch_to "$i" "$prev" "$was_installed"; then
        $schema_changed && is_demo "$i" && deploy/demo-reset.sh
        record "$i" "$tag" "failed, rolled back to $prev"
        log "rolled back: $i is running $prev again"
    else
        record "$i" "$tag" "failed, ROLLBACK TO $prev ALSO FAILED"
        log "ROLLBACK FAILED: $i is down, needs manual attention"
    fi
    return 1
}

do_rollback() {  # instance
    local i=$1 cur prev
    cur=$(current_tag "$i"); prev=$(previous_tag "$i")
    [[ -n $prev ]] || { log "no previous version recorded for $i"; return 1; }
    log "rolling back $i: $cur -> $prev"
    if switch_to "$i" "$prev"; then
        echo "$prev" > "$STATE_DIR/$i.current"
        echo "$cur"  > "$STATE_DIR/$i.previous"
        is_demo "$i" && deploy/demo-reset.sh
        record "$i" "$prev" "rollback from $cur"
        log "OK: $i is running $prev"
        if ! is_demo "$i"; then
            log "The database was NOT restored. If $cur ran migrations, the newest pre-deploy backup is:"
            ls -1t "$ENV_DIR/backups/$i/$i-predeploy-"*.sql.gz 2>/dev/null | head -1
        fi
    else
        record "$i" "$prev" "rollback from $cur FAILED"
        log "ROLLBACK FAILED: $i is not healthy on $prev"
        return 1
    fi
}

do_status() {
    for f in "$ENV_DIR"/*.env; do
        local i; i=$(basename "$f" .env)
        local health=down
        HEALTH_TIMEOUT=3 healthy "$i" false && health=healthy
        printf '%-6s %-8s current=%-12s previous=%s\n' "$i" "$health" \
            "$(current_tag "$i" | cut -c1-12)" "$(previous_tag "$i" | cut -c1-12)"
    done
    git fetch -q origin main 2>/dev/null || true
    local newest; newest=$(newest_published) && echo "newest published: ${newest:0:12} ($(git log -1 --format='%cr: %s' "$newest"))"
    echo "--- last deploys:"
    tail -5 "$ENV_DIR/deploy.log" 2>/dev/null || true
}

published() {  # sha: CI published all images for it (images are public, no login needed)
    local img
    for img in carmen-app carmen-web carmen-web-demo; do
        docker manifest inspect "ghcr.io/larsmw/$img:$1" >/dev/null 2>&1 || return 1
    done
}

newest_published() {  # newest commit on origin/main with published images (checks the last 20)
    local sha
    for sha in $(git rev-list -n 20 origin/main); do
        if published "$sha"; then echo "$sha"; return 0; fi
    done
    return 1
}

usage() { sed -n '4,8p' "$0" | sed 's/^# \{0,1\}//' >&2; exit 2; }

cmd=${1:-}; instance=${2:-}
[[ $cmd == status || ( -n $instance && -f "$ENV_DIR/$instance.env" ) ]] || usage

# deploy: pick the version, then re-run this script from that version's checkout so the
# compose file and scripts match the images being deployed
if [[ $cmd == deploy && -z ${CARMEN_CHECKED_OUT:-} ]]; then
    git fetch -q origin main
    sha=${3:-}
    if [[ -z $sha ]]; then
        sha=$(newest_published) || { log "no published images for the last 20 commits on main"; exit 1; }
        [[ $sha == $(git rev-parse origin/main) ]] \
            || log "note: newest main ($(git rev-parse --short origin/main)) has no published images (yet); using ${sha:0:12}"
    else
        sha=$(git rev-parse --verify -q "$sha^{commit}") || { log "unknown commit $3"; exit 1; }
        git merge-base --is-ancestor "$sha" origin/main || { log "$sha is not on main"; exit 1; }
        published "$sha" || { log "no published images for $sha (did the Publish images workflow pass?)"; exit 1; }
    fi
    git checkout -q --detach "$sha"
    CARMEN_CHECKED_OUT=1 exec deploy/deploy.sh deploy "$instance" "$sha"
fi

# One deploy at a time
exec 9>"$ENV_DIR/deploy.lock"
flock -w 900 9 || { log "another deploy is still running"; exit 1; }

case $cmd in
    deploy)   do_deploy "$instance" "$3" ;;
    build)
        tag="local-$(git rev-parse --short=12 HEAD)"
        IMAGE_TAG=$tag compose "$instance" build app web
        do_deploy "$instance" "$tag" ;;
    rollback) do_rollback "$instance" ;;
    status)   do_status ;;
    *) usage ;;
esac
