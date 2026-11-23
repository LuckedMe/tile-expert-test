# Deploying `tile-expert-test` to the production VPS

Step-by-step guide to deploy, update, back up and roll back `tile-expert-test` (JSON REST API: Symfony + MySQL + Manticore) on the VPS. The server itself (Docker, the shared Traefik, the Cloudflare Tunnel, swap, ufw) is prepared once by `vps-setup/docs/vps-setup.md`; the project part of that guide is `vps-setup/docs/projects/tile-expert-test.md` (step 23). This file is the project-side, self-contained version of it.

[main-dockerfile-fix.md](main-dockerfile-fix.md) explains **why** the `Dockerfile` and the prod compose file look the way they do. Read it if the build or the start fails.

> Conventions: the examples use the domain `tile-expert-test.lucked.me`, the server user `dad`, the Tailscale address `100.91.57.78` and the project folder `/home/dad/workspace/www/tile-expert-test`. Replace them with your own values.

## 1. Architecture

```
Browser ─https─► Cloudflare (TLS, redirect to https) ─tunnel─► cloudflared ─http─► traefik:80
              Host: tile-expert-test.lucked.me │ router from the labels of the nginx service
                                               ▼
                              nginx ─fastcgi─► php-fpm ─► mysql
                                                    └───► manticore (orders search index)
```

- The VPS has no public IP. Cloudflare terminates TLS, so nginx in the stack listens on plain HTTP `:80`: no certificates and no http→https redirect in the project.
- `docker-compose.prod.yaml` (project name `tile-expert-test-prod`) starts `php-fpm`, `nginx`, `mysql` and `manticore`. Both application images are **built on the server** from the root `Dockerfile` (targets `app` and `nginx`); PHP and Composer are not needed on the host.
- **Nothing is published on the host.** Traefik reaches `nginx` through the external `reverse-proxy` network, using the labels on the `nginx` service. (Docker published ports bypass `ufw`, and MySQL must never be public.) There is no Traefik file to create and no `ufw` rule to add.
- Persistent data lives in two named volumes: `mysql` (the source of truth) and `manticore` (the search index, rebuilt from MySQL with `app:order:reindex`).
- Migrations run automatically when `php-fpm` starts (`RUN_MIGRATIONS=1`).

| URL | Purpose |
|---|---|
| `https://tile-expert-test.lucked.me/api/products?factory=&collection=&article=` | price of a tile from tile.expert |
| `https://tile-expert-test.lucked.me/api/orders` | grouped orders (GET), create an order (POST) |
| `https://tile-expert-test.lucked.me/api/orders/{id}` | one order with its articles |
| `https://tile-expert-test.lucked.me/api/doc` | Swagger UI |

## 2. Prerequisites

On the server (done once, see `vps-setup.md`):

- [ ] Docker with the Compose plugin (`docker compose version`); the `reverse-proxy` network exists (`docker network ls`); Traefik is running with `--providers.docker.network=reverse-proxy` and `exposedByDefault=false`.
- [ ] `cloudflared` is running; a tunnel (here `vps`) points to `traefik:80`.
- [ ] **Swap is enabled** (`free -h`). Building the PHP image compiles extensions; on a small VPS the build is killed (exit 137) without swap.
- [ ] Free disk space: `df -h /home` (the first build needs 2–3 GB).
- [ ] The domain is in Cloudflare, with "Always Use HTTPS" on.

On your machine:

- [ ] Access to the GitHub repository `LuckedMe/tile-expert-test` and to the server over ssh (Tailscale).
- [ ] The latest code is pushed to `main` (the server pulls from GitHub).

## 3. Cloudflare: public hostname

Zero Trust → Networks → Tunnels → `vps` → **Published application routes** → **Add**:

| Subdomain | Domain | Path | Type | URL |
|---|---|---|---|---|
| `tile-expert-test` | `lucked.me` | *(empty)* | `HTTP` | `traefik:80` |

Cloudflare creates the proxied CNAME record `tile-expert-test` itself (delete an existing DNS record with that name first). The name is one level below `lucked.me`, so the free Universal SSL certificate covers it.

## 4. Clone the repository on the server

The server needs read access to the repository. GitHub accepts one public key as a Deploy key on **one** repository only, so create a dedicated key:

```bash
ssh dad@100.91.57.78

ssh-keygen -t ed25519 -C "dad@vps-main tile-expert-test" -f ~/.ssh/id_ed25519_tile_expert_test -N ""
cat ~/.ssh/id_ed25519_tile_expert_test.pub    # GitHub: LuckedMe/tile-expert-test → Settings → Deploy keys → Add (read-only)

cat >> ~/.ssh/config <<'CFG'
Host github-tile-expert-test
    HostName github.com
    User git
    IdentityFile ~/.ssh/id_ed25519_tile_expert_test
    IdentitiesOnly yes
CFG
chmod 600 ~/.ssh/config

ssh -T git@github-tile-expert-test            # "Hi LuckedMe/tile-expert-test! You've successfully authenticated"
cd /home/dad/workspace/www
git clone git@github-tile-expert-test:LuckedMe/tile-expert-test.git tile-expert-test
cd tile-expert-test
```

If the server's existing key is not a Deploy key anywhere else, you can skip the alias and clone with `git@github.com:LuckedMe/tile-expert-test.git`.

> The compose file sets `name: tile-expert-test-prod`, so containers and volumes are named `tile-expert-test-prod-*` / `tile-expert-test-prod_*` whatever the folder is called. Do not change that name after the first start, or Docker creates new empty volumes (the database would "disappear").

## 5. Configure `.env.prod.local`

```bash
cd /home/dad/workspace/www/tile-expert-test
cp .env.prod.local.dist .env.prod.local
chmod 600 .env.prod.local

openssl rand -hex 32        # → APP_SECRET
openssl rand -hex 24        # → MYSQL_PASSWORD
openssl rand -hex 24        # → MYSQL_ROOT_PASSWORD

nano .env.prod.local
```

The file is git-ignored and is never committed. Compose reads it twice: `--env-file` fills the `${...}` values of `docker-compose.prod.yaml`, `env_file` passes the same variables to the application containers.

| Variable | Value |
|---|---|
| `APP_IMAGE` / `APP_TAG` | leave `tile-expert-test` / `latest` |
| `MANTICORE_VERSION` | leave `32.1.3` (the version the index format was tested with) |
| `RUN_MIGRATIONS` | `1`: migrations run when `php-fpm` starts |
| `MYSQL_DATABASE` / `MYSQL_USER` | `tile_expert_test` / `tile_expert_test` |
| `MYSQL_PASSWORD` / `MYSQL_ROOT_PASSWORD` | generated (`openssl rand -hex 24`: hex only, so nothing breaks the `DATABASE_URL`). They are used when MySQL creates its data directory on the **first** start; changing them later does not change an existing database. |
| `APP_SECRET` | output of `openssl rand -hex 32` |
| `TIMEZONE` | `UTC` or `Europe/Kyiv` |
| `APP_DOMAIN` | `tile-expert-test.lucked.me` (the Traefik router rule uses it) |
| `DEFAULT_URI` | `https://tile-expert-test.lucked.me/` |
| `CORS_ALLOW_ORIGIN` | `^https://tile-expert-test\.lucked\.me$` (add other origins with `\|` if other sites call the API from a browser) |
| `PRODUCT_PAGE_URL_TEMPLATE` / `PRODUCT_PRICE_CACHE_TTL` | leave the defaults (page of tile.expert, price cached for 3600 s) |

Not set here on purpose: `APP_ENV=prod` and `APP_DEBUG=0` (the image and the compose file set them), `DATABASE_URL` (built by the compose file from `MYSQL_*`), `MANTICORE_URL` and `MESSENGER_TRANSPORT_DSN=sync://` (no worker is needed).

Never put `!ChangeMe!` values into the file: `bin/deploy.sh` refuses to run with a placeholder.

## 6. First start

Run every `docker compose` command of this guide from the project folder (the `-f` and `--env-file` options select the production stack and its settings).

```bash
cd /home/dad/workspace/www/tile-expert-test

docker compose -f docker-compose.prod.yaml --env-file .env.prod.local config -q && echo OK                    # the compose file and the env file are valid
docker compose -f docker-compose.prod.yaml --env-file .env.prod.local config | grep -c published              # 0: nothing is published on the host
docker compose -f docker-compose.prod.yaml --env-file .env.prod.local config | grep -E 'traefik|reverse-proxy' | head   # the labels and the network are there

docker compose -f docker-compose.prod.yaml --env-file .env.prod.local up -d --build                           # the first build takes 5–15 minutes (PHP extensions, composer)
docker compose -f docker-compose.prod.yaml --env-file .env.prod.local ps                                      # php-fpm, nginx, mysql (healthy), manticore: Up
docker compose -f docker-compose.prod.yaml --env-file .env.prod.local logs -f php-fpm                         # "Successfully migrated", "ready to handle connections"; Ctrl+C
```

Or use the script, which does the same checks and is the way to deploy every later release (section 8):

```bash
bin/deploy.sh --no-pull --reindex
```

The Manticore index is created automatically when the first order is saved. When the database already has orders (restored from a backup, or data imported before), build the index once:

```bash
docker compose -f docker-compose.prod.yaml --env-file .env.prod.local exec php-fpm bin/console app:order:reindex      # "Done, N orders indexed."
```

The search works without the index too (the API falls back to MySQL `LIKE`), so a missing index is never an outage.

## 7. Verify

```bash
# on the server, through Traefik with the right Host header
curl -sI -H 'Host: tile-expert-test.lucked.me' http://127.0.0.1/api/doc | head -1                    # HTTP/1.1 200 OK
curl -s  -H 'Host: tile-expert-test.lucked.me' 'http://127.0.0.1/api/orders?group_by=month'            # {"items":[...],"meta":{...}}

# from anywhere
curl -sI http://tile-expert-test.lucked.me/api/doc  | head -3      # 301 to https (Cloudflare)
curl -sI https://tile-expert-test.lucked.me/api/doc | head -1      # 200, valid certificate
curl -s  https://tile-expert-test.lucked.me/api/orders             # JSON
```

Create a test order (the request from `tests/_https/orders/post.orders.http`) and look it up:

```bash
curl -s -o /dev/null -w '%{http_code}\n' -X POST https://tile-expert-test.lucked.me/api/orders \
  -H 'Content-Type: application/json' -d @- <<'JSON'
{"hash":"aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa","token":"bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb",
 "email":"deploy-check@example.com","vat_type":"vat-type-1","payment_type":"visa","locale":"en_GB","currency":"EUR",
 "measure":"m","name":"deploy check","articles":[{"article_id":5,"amount":2,"price":10.5,"weight":20,
 "packaging_count":1,"pallet":50,"packaging":1.44}]}
JSON                                                                                   # 204
curl -s 'https://tile-expert-test.lucked.me/api/orders?query=deploy-check'             # one group with count 1
```

Delete the test order afterwards (`docker compose -f docker-compose.prod.yaml --env-file .env.prod.local exec mysql sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE" -e "DELETE FROM orders WHERE email=\"deploy-check@example.com\""'`, then `app:order:reindex`), or keep it as a smoke-test record.

In the browser: `https://tile-expert-test.lucked.me/api/doc` opens Swagger UI. `docker ps` must show no published port for the project containers (only Traefik has `ports:`). Ready-to-run requests for every endpoint are in `tests/_https/orders/*.http` (change the `@host` variable).

`GET /api/products` depends on tile.expert, which protects its pages with a Cloudflare challenge: from a server IP it can answer `403`, which the API reports as `400` (a limitation of the project, not of the deployment).

## 8. Updating (every release)

Use the deploy script, run **on the server**:

```bash
cd /home/dad/workspace/www/tile-expert-test
bin/deploy.sh                  # git pull --ff-only + backup + build + up -d + status check + curl + image prune
```

To deploy **from your own machine** instead, push your commits and run:

```bash
bin/deploy.sh --remote                                   # ssh to DEPLOY_HOST and run the script there
bin/deploy.sh --remote --reindex                         # the same, and rebuild the search index afterwards
bin/deploy.sh --remote --host dad@<server> --dir /srv/tile-expert-test
make prod-deploy-remote                                  # shortcut for `bin/deploy.sh --remote`
```

`--remote` opens an ssh session (with a tty, so docker and git show their progress) and runs `bin/deploy.sh` in `DEPLOY_DIR` on the server with the same options. The script must therefore already exist in the server's checkout (it is part of the repository: after the very first clone it is there), and the server still pulls from GitHub through its deploy key (section 4). The script warns if you have unpushed commits: the server will not get them.

Settings (environment variables, all optional):

| Variable | Default | Meaning |
|---|---|---|
| `DEPLOY_HOST` | `dad@100.91.57.78` (Tailscale IP) | ssh target for `--remote` |
| `DEPLOY_DIR` | `/home/dad/workspace/www/tile-expert-test` | project folder on the server |
| `BACKUP_SCRIPT` | `/home/dad/workspace/backups/backup-tile-expert-test.sh` | database backup run before every update (section 9); a missing script only produces a warning |
| `APP_URL` | `https://tile-expert-test.lucked.me/api/doc` | checked with `curl` at the end, must answer 2xx/3xx; set it empty to skip the check |

`BACKUP_SCRIPT` and `APP_URL` set on your machine are passed to the server with `--remote`.

Options:

| Option | Meaning |
|---|---|
| `--no-backup` | skip the pre-update backup |
| `--ref <tag\|branch\|commit>` | deploy that revision instead of the current branch head (also used for a rollback) |
| `--no-pull` | deploy the code already checked out (no `git fetch`/`pull`) |
| `--reindex` | rebuild the Manticore index after the update (`app:order:reindex`): use it after a release that changes the indexed fields, or after restoring a database |
| `--no-prune` | keep dangling images |
| `--remote`, `--host`, `--dir` | run on the server over ssh (see above) |
| `--help` | print the usage |

The script stops at the first failing step (`set -e`). It refuses to run when `.env.prod.local` is missing or still contains a `!ChangeMe!` placeholder, when the working tree has local changes (unless `--no-pull`), and it exits non-zero if `php-fpm`, `nginx`, `mysql` or `manticore` is not running afterwards or `APP_URL` does not answer. Migrations run automatically at the start of `php-fpm`. `make prod-deploy` is a shortcut for `bin/deploy.sh`.

It is equivalent to these manual steps:

```bash
git pull --ff-only
docker compose -f docker-compose.prod.yaml --env-file .env.prod.local up -d --build --remove-orphans
docker compose -f docker-compose.prod.yaml --env-file .env.prod.local ps
docker compose -f docker-compose.prod.yaml --env-file .env.prod.local logs --tail 50 php-fpm                       # migrations, no errors
docker image prune -f
```

- A change of `.env.prod.local` needs only `docker compose -f docker-compose.prod.yaml --env-file .env.prod.local up -d` (the containers are recreated with the new environment).
- If `composer.json` changed, `--build` reinstalls the dependencies; unchanged layers come from the cache.
- Do not `docker compose down -v` to "restart": it deletes the volumes.

**Roll back a bad release:**

```bash
git log --oneline -5
bin/deploy.sh --ref <previous-commit>            # or: bin/deploy.sh --remote --ref <previous-commit>
```

The checkout is detached afterwards; the next plain `bin/deploy.sh` needs `git checkout main` first. Migrations are not reverted automatically: if the failed release changed the schema, restore the database from the backup taken before the update (section 9), or run `docker compose -f docker-compose.prod.yaml --env-file .env.prod.local exec php-fpm bin/console doctrine:migrations:migrate prev`.

## 9. Backups

MySQL is the source of truth. The Manticore index is rebuilt with `app:order:reindex`, so it is not backed up. Create `/home/dad/workspace/backups/backup-tile-expert-test.sh` (nano, or a heredoc with a delimiter other than `EOF`):

```bash
#!/usr/bin/env bash
set -euo pipefail
cd /home/dad/workspace/www/tile-expert-test
STAMP=$(date +%F_%H%M)
mkdir -p /home/dad/workspace/backups/dump
docker compose -f docker-compose.prod.yaml --env-file .env.prod.local exec -T mysql \
  sh -c 'exec mysqldump --single-transaction --routines -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' \
  | gzip > /home/dad/workspace/backups/dump/tile-expert-test-$STAMP.sql.gz
find /home/dad/workspace/backups/dump -name 'tile-expert-test-*.sql.gz' -mtime +7 -delete
```

```bash
chmod 700 /home/dad/workspace/backups/backup-tile-expert-test.sh
/home/dad/workspace/backups/backup-tile-expert-test.sh && ls -lh /home/dad/workspace/backups/dump | tail -3
( crontab -l 2>/dev/null; echo '20 3 * * * /home/dad/workspace/backups/backup-tile-expert-test.sh >> /home/dad/workspace/backups/backup.log 2>&1' ) | crontab -
```

`bin/deploy.sh` runs this script before every update (`--no-backup` skips it).

Restore (into the running stack):

```bash
gunzip -c /home/dad/workspace/backups/dump/tile-expert-test-<stamp>.sql.gz \
  | docker compose -f docker-compose.prod.yaml --env-file .env.prod.local exec -T mysql sh -c 'exec mysql -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"'
docker compose -f docker-compose.prod.yaml --env-file .env.prod.local exec php-fpm bin/console app:order:reindex
```

## 10. Stop and remove

- Take the site offline only: delete the `tile-expert-test` route in the tunnel (Zero Trust → Tunnels → `vps`), or `docker compose -f docker-compose.prod.yaml --env-file .env.prod.local stop nginx`.
- Stop everything, keep the data: `docker compose -f docker-compose.prod.yaml --env-file .env.prod.local down` (the volumes stay).
- Full removal (**destroys the database and the search index**; back up first): `docker compose -f docker-compose.prod.yaml --env-file .env.prod.local down -v`, then `rm -rf /home/dad/workspace/www/tile-expert-test`, and remove the Cloudflare route and the Deploy key.
- Database console (there is no Adminer in production): `docker compose -f docker-compose.prod.yaml --env-file .env.prod.local exec mysql sh -c 'exec mysql -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"'`.

## 11. Troubleshooting

| Symptom | Cause / fix |
|---|---|
| `404 page not found` from Traefik | nginx is not in the `reverse-proxy` network or has no labels (`docker compose -f docker-compose.prod.yaml --env-file .env.prod.local config \| grep traefik`), or the Host is not `APP_DOMAIN` (the router rule uses it). The router name `tile-expert-test` must be unique among the projects. |
| `502`/`504` from Traefik | nginx or php-fpm is not up: `docker compose -f docker-compose.prod.yaml --env-file .env.prod.local ps`, `docker compose -f docker-compose.prod.yaml --env-file .env.prod.local logs nginx php-fpm`. |
| `502` from Cloudflare | The tunnel route URL is not `http://traefik:80`, or the `tile-expert-test` hostname is missing in the tunnel. |
| `network reverse-proxy declared as external, but could not be found` | The shared infrastructure is not running (`vps-setup.md` step 10): `docker network create reverse-proxy` or start Traefik. |
| Build killed (137) | Add swap (`vps-setup.md` step 6); `docker system df`, `docker image prune -f` for disk. |
| Build fails: `Class "...Bundle" not found` | A bundle registered for all environments is only in `require-dev`: move it to `require` (see `docs/main-dockerfile-fix.md`, section 6). |
| `bin/deploy.sh`: `.env.prod.local still contains !ChangeMe! placeholders` | Fill in the file (section 5). |
| `bin/deploy.sh`: `the working tree has local changes` | Someone edited files on the server: `git status`, `git diff`; `git stash` or `git checkout -- <file>`. Never edit code on the server. |
| `bin/deploy.sh`: `Not possible to fast-forward` | The server's branch diverged (a force-push or a local commit). Inspect with `git log --oneline --graph --all -10`; then `git reset --hard origin/main` only if nothing on the server must be kept. |
| `bin/deploy.sh --remote`: `Permission denied (publickey)` | Your key is not authorised on the server, or Tailscale is down (`tailscale status`). |
| php-fpm restarts in a loop | `docker compose -f docker-compose.prod.yaml --env-file .env.prod.local logs php-fpm`: a wrong `.env.prod.local` (empty `APP_SECRET`), MySQL not healthy yet, or a failed migration. |
| `SQLSTATE[HY000] [1045] Access denied` | `MYSQL_PASSWORD` in `.env.prod.local` differs from the one MySQL was initialised with (the first start). Restore the old value, or remove the `mysql` volume if the database is empty. |
| Orders search returns nothing | The index is empty: `docker compose -f docker-compose.prod.yaml --env-file .env.prod.local exec php-fpm bin/console app:order:reindex` (without Manticore the API falls back to MySQL `LIKE`). |
| `POST /api/orders` is `422` with `hash ... already exist` | The `hash` of the order is not unique: the request is correct, send another hash. |
| `GET /api/products` is `400` (`HTTP/2 403`) | tile.expert answers server IPs with a Cloudflare challenge: a limitation of the project. |
| Links with `http://`, wrong client IP in logs | `--entrypoints.web.forwardedheaders.trustedips` is missing in Traefik (`vps-setup.md` step 18.3). |

## 12. Quick reference

```bash
cd /home/dad/workspace/www/tile-expert-test      # every docker compose command below runs from this folder

bin/deploy.sh                      # update the server (run on the server)
bin/deploy.sh --remote             # update the server from your machine (push first)
bin/deploy.sh --reindex            # ...and rebuild the search index
bin/deploy.sh --ref <commit>       # roll back

docker compose -f docker-compose.prod.yaml --env-file .env.prod.local ps                             # status
docker compose -f docker-compose.prod.yaml --env-file .env.prod.local logs -f php-fpm                # application log
docker compose -f docker-compose.prod.yaml --env-file .env.prod.local exec php-fpm bin/console about # Symfony: environment prod, debug false
docker compose -f docker-compose.prod.yaml --env-file .env.prod.local exec php-fpm bin/console app:order:reindex
docker compose -f docker-compose.prod.yaml --env-file .env.prod.local up -d                          # apply a changed .env.prod.local

# stop / start without deleting anything
docker compose -f docker-compose.prod.yaml --env-file .env.prod.local stop
docker compose -f docker-compose.prod.yaml --env-file .env.prod.local start
```

| Make target | Same as |
|---|---|
| `make prod-deploy` | `bin/deploy.sh` |
| `make prod-deploy-remote` | `bin/deploy.sh --remote` |
| `make prod-build` / `prod-up` / `prod-down` / `prod-ps` / `prod-logs` | `docker compose -f docker-compose.prod.yaml --env-file .env.prod.local build / up -d --build / down / ps / logs -f` |
| `make prod-console` | a shell in the production `php-fpm` container |
| `make prod-reindex` | `bin/console app:order:reindex` in the production container |
