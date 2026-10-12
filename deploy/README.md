# Deploying CaRMen

Each instance is a Docker Compose project (PostgreSQL + PHP-FPM API + nginx serving the
built frontend) listening on `127.0.0.1:<HTTP_PORT>`. The host's nginx terminates TLS and
proxies to it.

Live on ssdvps (91.210.57.191):

| Instance | URL                                | Port | Env file              |
|----------|------------------------------------|------|-----------------------|
| `crm`    | https://crm.carmen-crm.net         | 8081 | `/srv/carmen/crm.env`  |
| `demo`   | https://www.carmen-crm.net/demo/   | 8082 | `/srv/carmen/demo.env` |

## Layout on the server

```
/srv/carmen/src/          git checkout of this repo
/srv/carmen/crm.env       instance config + secrets (chmod 600, never in git)
/srv/carmen/demo.env
/srv/carmen/backups/crm/  nightly pg_dump of the real CRM (last 14 kept)
/etc/cron.d/carmen        demo reset + backup schedule
```

## Update to the latest code

```bash
cd /srv/carmen/src && git pull && deploy/deploy.sh        # all instances
deploy/deploy.sh demo                                     # just one
```

Containers run pending migrations on start.

## New instance

1. `cp deploy/instance.env.example /srv/carmen/<name>.env`, `chmod 600`, fill in
   (`openssl rand -hex 32` for secrets).
2. `deploy/deploy.sh <name>`
3. Create the first admin before the site is public:
   `docker compose -p carmen-<name> --env-file /srv/carmen/<name>.env -f deploy/compose.yaml exec app php bin/console app:create-admin`
4. Add an nginx vhost proxying to the port (see `host-nginx/`).

## Public demo

`demo.env` sets `DEMO_ACCOUNT_EMAIL` / `DEMO_ACCOUNT_PASSWORD`: shown on the login page,
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
gunzip -c /srv/carmen/backups/crm/<file>.sql.gz | \
  docker compose -p carmen-crm --env-file /srv/carmen/crm.env -f deploy/compose.yaml exec -T db psql -U carmen carmen
```
(into an empty database: stop `app`, drop/recreate the `carmen` database first)
