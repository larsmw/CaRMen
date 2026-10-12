# Deploying CaRMen

Each instance is a Docker Compose project (PostgreSQL + PHP-FPM API + nginx serving the
built frontend) listening on `127.0.0.1:<HTTP_PORT>`. The host's nginx terminates TLS and
proxies to it.

## How a release gets out

1. Merge to `main`. The **Tests** workflow runs.
2. If it passes, the **Deploy** workflow builds `ghcr.io/larsmw/carmen-{app,web,web-demo}:<sha>`
   (public images) and deploys that commit to the **demo** automatically.
3. Check https://www.carmen-crm.net/demo/, then deploy the **real CRM**: GitHub → Actions →
   Deploy → *Run workflow* → instance `crm`, action `deploy` (empty SHA = latest main).
   The same button with action `rollback` switches back to the previous version.

GitHub reaches the server over SSH with a key that can only run the deploy (see below).

### Fallback: deploy from the server

The hosting provider sometimes drops SSH from GitHub's runners, so the Deploy job can fail
with "Could not reach the server" even though the images were published. Then log in and run
the same deploy from the server (the failed run's summary shows the exact command):

```bash
cd /srv/carmen/src
deploy/deploy.sh status          # what runs where + newest published version
deploy/deploy.sh deploy demo     # newest main commit with published images
deploy/deploy.sh deploy crm      # or a specific one: deploy crm <sha>
deploy/deploy.sh rollback crm
```

### What a deploy does

`deploy <instance> [sha]` checks this checkout out at the commit being deployed (so the compose
file and scripts match the images), pulls the images, backs up the database (crm only),
switches containers and health-checks the instance. If the check fails within 2 minutes it
switches back to the previous version, restoring the pre-deploy backup first if the failed
version changed the schema.

A manual **rollback** does not restore the database; it prints the newest pre-deploy backup
in case the newer version migrated it. Every deploy and rollback is logged to
`/srv/carmen/deploy.log`.

If GitHub/ghcr.io itself is unavailable, `deploy/deploy.sh build <instance>` builds the images
from the server's checkout instead and deploys them the same way.

### CI access to the server

The `DEPLOY_SSH_KEY` secret is a key in root's `authorized_keys` restricted to a forced
command, `/usr/local/sbin/carmen-ci-deploy` (a copy of `deploy/ci-deploy.sh`): it only accepts
`deploy <crm|demo> <sha on main>` or `rollback <crm|demo>`. `DEPLOY_KNOWN_HOSTS` pins the
server's host key. After changing `ci-deploy.sh`, copy it to `/usr/local/sbin/` again.

## Layout on the server

```
/srv/carmen/src/          git checkout (detached at the last deployed commit)
/srv/carmen/crm.env       instance config + secrets (chmod 600, never in git)
/srv/carmen/demo.env
/srv/carmen/state/        <instance>.current / .previous: deployed image tags
/srv/carmen/deploy.log    one line per deploy / rollback
/srv/carmen/backups/crm/  crm-nightly-* (last 14) and crm-predeploy-* (last 10)
/etc/cron.d/carmen        demo reset + backup schedule
```

Containers run pending migrations on start.

## New instance

1. `cp deploy/instance.env.example /srv/carmen/<name>.env`, `chmod 600`, fill in
   (`openssl rand -hex 32` for secrets).
2. `deploy/deploy.sh deploy <name>` (or `build <name>`)
3. Create the first admin before the site is public:
   `docker compose -p carmen-<name> --env-file /srv/carmen/<name>.env -f deploy/compose.yaml exec app php bin/console app:create-admin`
4. Add an nginx vhost proxying to the port (see `host-nginx/`).

## Public demo

`demo.env` sets `DEMO_ACCOUNT_EMAIL` / `DEMO_ACCOUNT_PASSWORD`: shown on the login page
(served by `/api/install/status`),
protected from changes, password reset disabled. `deploy/demo-reset.sh` wipes the database
and reloads the demo login + demo data; cron runs it nightly.

## Host nginx (not managed by Puppet)

- `host-nginx/crm.carmen-crm.net.conf` → `/etc/nginx/sites-available/`, symlinked in
  `sites-enabled/`. Certificate via `certbot certonly --nginx -d crm.carmen-crm.net`
  (renewed by certbot's timer).
- `host-nginx/carmen-demo.location.conf` → `/etc/nginx/snippets/`, included by one line in
  the Puppet-generated `www.carmen-crm.net-https.conf`. **A Puppet run on ssdvps will drop that
  `include` line** and `/demo` stops working; re-add it after any Puppet run.

## Restore a backup

```bash
c="docker compose -p carmen-crm --env-file /srv/carmen/crm.env -f deploy/compose.yaml"
$c stop app
$c exec -T db psql -U carmen -d carmen -c 'DROP SCHEMA public CASCADE; CREATE SCHEMA public;'
gunzip -c /srv/carmen/backups/crm/<file>.sql.gz | $c exec -T db psql -U carmen -d carmen -q
IMAGE_TAG=$(cat /srv/carmen/state/crm.current) $c up -d
```
