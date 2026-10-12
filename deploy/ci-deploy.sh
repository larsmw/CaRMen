#!/bin/bash
# Forced command for the CI deploy SSH key (installed as /usr/local/sbin/carmen-ci-deploy,
# outside the checkout it updates). The key can do nothing but:
#   deploy <crm|demo> <40-char commit SHA on origin/main>
#   rollback <crm|demo>
set -euo pipefail
refuse() { echo "refused: $*" >&2; exit 2; }

read -r action instance sha extra <<< "${SSH_ORIGINAL_COMMAND:-}"
[[ -z ${extra:-} ]] || refuse "too many arguments"
[[ $instance =~ ^(crm|demo)$ ]] || refuse "unknown instance '$instance'"

cd /srv/carmen/src
case $action in
    deploy)
        [[ $sha =~ ^[0-9a-f]{40}$ ]] || refuse "not a full commit SHA"
        git fetch -q origin main
        git merge-base --is-ancestor "$sha" origin/main || refuse "$sha is not on main"
        # Scripts and compose file from the version being deployed
        git checkout -q --detach "$sha"
        exec deploy/deploy.sh deploy "$instance" "$sha"
        ;;
    rollback)
        [[ -z $sha ]] || refuse "rollback takes no SHA"
        exec deploy/deploy.sh rollback "$instance"
        ;;
    *) refuse "unknown action '$action'" ;;
esac
