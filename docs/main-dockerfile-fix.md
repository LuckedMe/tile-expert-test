# Main (production) Dockerfile: instructions

How to make and keep the root `Dockerfile` of `tile-expert-test` ready for the VPS. Used by the global Claude skill `fix-dockerfile` (`~/.claude/skills/fix-dockerfile/`, with the checker `scripts/check.sh`; it is shared by all projects, so the project facts live here), and readable on its own.

The server-side steps (Cloudflare, deploy key, secrets, first start, updates, backups) are **not** repeated here: see [deploy.md](deploy.md). The common server setup (Docker, Traefik, Cloudflare Tunnel) is described in the `vps-setup` repository (`docs/vps-setup.md`, step 23 for this project).

## 1. What the VPS expects

```
Browser ─https─► Cloudflare (TLS, redirect to https) ─tunnel─► cloudflared ─http─► traefik:80
              Host: tile-expert-test.lucked.me │ router from the labels of the nginx service
                                               ▼
                              nginx ─fastcgi─► php-fpm ─► mysql
                                                    └───► manticore (orders search index)
```

| Fact | Consequence for the image / compose |
|---|---|
| The VPS has no public IP; Cloudflare terminates TLS | nginx listens on plain `:80` only. No certificates, no http→https redirect in nginx or in the Traefik labels (a `redirectscheme` middleware would loop). |
| Published Docker ports bypass `ufw` | `docker-compose.prod.yaml` publishes **no** port. Traefik reaches `nginx` through the external `reverse-proxy` network, using the labels on the service. |
| Traefik routes by `Host` | The label `Host(\`${APP_DOMAIN}\`)` uses `APP_DOMAIN` from `.env.prod.local`. Router and service names (`tile-expert-test`) must be unique among all projects on the server. |
| The image is **built on the server** (`docker compose ... up -d --build`) | Small RAM/disk: layers must be cache-friendly (manifests first), Composer is not installed on the host, `.dockerignore` must keep the build context small. |
| The compose project name is fixed (`tile-expert-test-prod`) | Volume names (`mysql`, `manticore`) must not be renamed after the first start, or the data "disappears". |
| Symfony trusts `X-Forwarded-*` from private ranges (`config/packages/framework.yaml`, `trusted_proxies`) | Nothing to do in nginx; the headers reach PHP through fastcgi, so the app sees `https` and the client IP. |
| The application needs MySQL and Manticore | Both are services of the prod compose file; the app reaches them by service name (`mysql`, `manticore`). Manticore is secondary data: the app keeps working without it (LIKE fallback). |

What the production image does **not** contain: Node/webpack (there is no frontend), the JWT keys, uploads, sessions, a messenger worker (`MESSENGER_TRANSPORT_DSN=sync://`), the dev packages and the test database.

## 2. Reference layout of the `Dockerfile`

| Stage | Base | Purpose |
|---|---|---|
| `php-base` | `php:8.4-fpm` | PHP extensions (`@composer zip opcache intl pdo_mysql bcmath`), user `app` (uid/gid 1000), `php.ini` + `php-fpm.d` + `php.prod.ini`, `APP_ENV=prod`, `APP_DEBUG=0` |
| `vendor` | `php-base` | `composer install --no-dev --no-autoloader --no-scripts` with a BuildKit cache mount; only `composer.json`, `composer.lock`, `symfony.lock` are copied, so the layer is cached until they change |
| `app` | `php-base` | code + `vendor`; `composer dump-autoload --no-dev --classmap-authoritative`; `post-install-cmd` (`assets:install public`: Swagger UI files into `public/bundles`); `var/cache/prod` removed (the entrypoint warms it at start); `var/{cache,log,share}` owned by `app`; entrypoint; `USER app:app`; `php-fpm` |
| `nginx` | `nginx:mainline-alpine` | `nginx.conf`, `conf.d/default.conf`, `public/` copied from `app` |
| (last) | `FROM app` | keeps `app` the default target of a plain `docker build .` |

If the `Dockerfile` is missing, rebuild it from this table (the existing `Dockerfile` of the repository is the reference). Keep the stage names: `docker-compose.prod.yaml` uses `target: app` and `target: nginx`.

Files the image depends on (all in the repository): `.docker/php-fpm/config/php.ini`, `php-fpm.d/www.conf`, `php.prod.ini` (OPcache without timestamp checks, `opcache.preload` → `config/preload.php`), `.docker/php-fpm/docker-entrypoint.sh` (cache warmup at start and `doctrine:migrations:migrate` when `RUN_MIGRATIONS=1`), `.docker/nginx/config/*`.

## 3. Audit checklist

The script `scripts/check.sh` checks the items marked (F*). Mark each item OK / fixed / needs a decision.

### Build context
- [ ] (F6) `.dockerignore` exists and excludes `.git`, `.idea`, `.claude`, `vendor`, `var`, `public/bundles`, `tests`, `docs`, `.docker/mysql/data`, `docker-compose*.yaml`, `.env.local`, `.env.*.local`, `config/secrets/prod/prod.decrypt.private.php`. Secrets and the local database must never enter the image.

### `php-base` / `vendor`
- [ ] (F1) `ARG PHP_VERSION` satisfies `composer.json` (`"php": ">=8.4"`) and equals the dev default `COMPOSE_PHP_VERSION` of `docker-compose.yaml`.
- [ ] (F7) `APP_ENV=prod` and `APP_DEBUG=0` are `ENV`s; `composer install` uses `--no-dev` and the BuildKit cache mount; `composer.lock` and `symfony.lock` are copied.
- [ ] (F2) Every PHP extension the app needs is installed: `ext-*` of `composer.json`/`composer.lock`, `pdo_mysql` (MySQL), and the core set of the dev image (`zip opcache intl bcmath`). Dev-only tooling (`pcov`, `xdebug`, `excimer`) and extensions this app does not use (`gd`, `exif`, `xsl`, `amqp`, `redis`, `pdo_pgsql`) stay out of the image. If the code starts to use one of them, add it to the single `install-php-extensions` line.
- [ ] **Runtime bundles are in `require`, not in `require-dev`.** `config/bundles.php` registers `TwigBundle` for all environments, and Swagger UI (`/api/doc`) needs it. `symfony/twig-bundle` must be in `composer.json` `require`; with `--no-dev` the build fails with `Class "Symfony\Bundle\TwigBundle\TwigBundle" not found` otherwise (it was only pulled in by the dev-only web profiler). The same applies to every bundle that is registered with `'all' => true`.

### `app`
- [ ] `composer dump-autoload --no-dev --classmap-authoritative` runs after `COPY . .`.
- [ ] `composer run-script --no-dev post-install-cmd` runs, so `public/bundles/nelmioapidoc` exists (Swagger UI).
- [ ] `var/cache/prod` is removed at build (the entrypoint warms it at start with the real environment of the container).
- [ ] `var/{cache,log,share}` exist and are `chown app:app`.
- [ ] (F7) `USER app:app`; the entrypoint runs `cache:warmup`, and the migrations when `RUN_MIGRATIONS=1`.
- [ ] `config/preload.php` exists (`php.prod.ini` has `opcache.preload` pointing at it). If it does not, remove the setting, or php-fpm will not start.
- [ ] (F4) Every `COPY` source exists.

### `nginx`
- [ ] `default.conf` has `root /var/www/html/public`, `try_files $uri /index.php$is_args$args`, `fastcgi_pass` to `php-fpm:9000`, and `internal` on the `index.php` location, so other `.php` files return 404.
- [ ] No `listen 443`, no `ssl_*`, no redirect, no `server_name` other than `_`.
- [ ] `public/` is copied `--from=app`.

### Compose / env (`docker-compose.prod.yaml`, `.env.prod.local.dist`)
- [ ] (F9) `name: tile-expert-test-prod` is unchanged.
- [ ] (F9) Services `php-fpm`, `nginx`, `mysql`, `manticore`; `restart: unless-stopped`; mysql has a healthcheck and `php-fpm` waits for it.
- [ ] (F9) **No `ports`** on any service. `nginx` is in `app` + the external `reverse-proxy` network and has the Traefik labels (`traefik.enable`, `traefik.docker.network=reverse-proxy`, service port 80, ``Host(`${APP_DOMAIN}`)``, entrypoint `web`). No `redirectscheme`, no `certresolver`.
- [ ] (F9) `php-fpm` and `nginx` use `target: app` / `target: nginx`.
- [ ] (F9) `mysql` hides the dev `init.sql` (`tmpfs: /docker-entrypoint-initdb.d`): it creates a test database and grants for the dev user `user`, which must not exist in production.
- [ ] (F9) Every `${VAR}` without a default is defined in `.env.prod.local.dist`; the file holds `!ChangeMe!` placeholders only. Real values live in `.env.prod.local` on the server (`chmod 600`, git-ignored, never committed).
- [ ] The application variables are passed to the container (`env_file: .env.prod.local`) and the compose file sets `DATABASE_URL` from `MYSQL_*`, `MANTICORE_URL=http://manticore:9308`, `MESSENGER_TRANSPORT_DSN=sync://`.

## 4. Fix procedure

1. Edit only what the checklist flags. Prefer the smallest change; keep the stage names.
2. Keep dev and prod separate: `docker-compose.yaml`, `docker-compose.override.yaml(.dist)` and `.docker/*/Dockerfile` are the dev stack.
3. Do not add Traefik files to the repository other than the labels in `docker-compose.prod.yaml`; there is nothing to create on the server outside the project folder.
4. After editing, run the script and the verification below.

## 5. Verification

Locally, from the project root (Docker with BuildKit):

```bash
# 0. the checker and the Dockerfile linter
bash ~/.claude/skills/fix-dockerfile/scripts/check.sh      # exit 0
docker build --check .

# 1. both targets build
docker build --target app   -t tile-expert-test:test .
docker build --target nginx -t tile-expert-test-nginx:test .

# 2. the image is a prod image, has no dev packages and has its static files
docker run --rm -e APP_SECRET=x tile-expert-test:test php bin/console about | grep -E 'Environment|Debug'   # prod / false
docker run --rm tile-expert-test:test ls public/bundles                                                    # nelmioapidoc
docker run --rm tile-expert-test:test id                                                                   # uid=1000(app)

# 3. compose is valid and publishes nothing
cp .env.prod.local.dist .env.prod.local        # throwaway local values: replace every !ChangeMe!, APP_DOMAIN=localhost
docker compose -f docker-compose.prod.yaml --env-file .env.prod.local config -q && echo OK
docker compose -f docker-compose.prod.yaml --env-file .env.prod.local config | grep -c published          # 0

# 4. run it. Nothing is published, so publish nginx for the test only with a scratch override
cat > /tmp/scratch-override.yaml <<'YAML'
services:
    nginx:
        ports:
            - "127.0.0.1:18080:80"
YAML
C="docker compose -f docker-compose.prod.yaml -f /tmp/scratch-override.yaml --env-file .env.prod.local"
$C up -d --build
curl -s -o /dev/null -w '%{http_code}\n' http://127.0.0.1:18080/api/doc                  # 200
curl -s http://127.0.0.1:18080/api/orders | head -c 120                                  # {"items":[],"meta":...}
$C exec -T php-fpm bin/console app:order:reindex                                         # Done, N orders indexed
$C down -v && rm .env.prod.local /tmp/scratch-override.yaml                               # clean up the test stack
```

`bin/deploy.sh --no-pull --no-backup --reindex` (with `APP_URL=` empty) runs the same stack through the deploy script and is a good last check before a release.

## 6. Troubleshooting

| Symptom | Cause / fix |
|---|---|
| Build fails in `app`: `Class "Symfony\Bundle\TwigBundle\TwigBundle" not found` (or another bundle) | A bundle registered for `all` environments is only in `require-dev`: `composer require` it (it must be in `require`). |
| Build killed (137) or `no space left on device` | Add swap on the server (`vps-setup/docs/vps-setup.md` step 6), `docker system df`, `docker image prune -f`. |
| php-fpm restarts: `Failed opening ... preload.php` | `opcache.preload` points to a missing file: restore `config/preload.php` or drop the setting in `php.prod.ini`. |
| php-fpm restarts, `SQLSTATE[HY000] [2002]` in the log | The entrypoint runs the migrations before MySQL accepts connections: `php-fpm` has `depends_on: mysql: service_healthy`; check `docker compose ... ps` and wait for the first MySQL start to finish. |
| `404 page not found` from Traefik | nginx is not in the `reverse-proxy` network or has no labels (`docker compose ... config \| grep traefik`), or `APP_DOMAIN` differs from the requested Host. |
| `502`/`504` from Traefik | nginx or php-fpm is not up: `docker compose ... ps`, `logs nginx php-fpm`. |
| `/api/doc` is a 500, `/api/orders` works | `public/bundles` is empty (`post-install-cmd` did not run) or TwigBundle is missing in the image. |
| `POST /api/orders` is a 400 with `Manticore error` | Old code: the search index is secondary and is created on the first order; update to the current version. Rebuild the index with `bin/deploy.sh --reindex` / `make prod-reindex`. |
| Orders search returns nothing after a restore | The Manticore index is empty: `app:order:reindex` (without Manticore the API falls back to MySQL `LIKE`). |
| Data disappeared after a redeploy | The compose project name or a volume name changed; keep `name: tile-expert-test-prod`. |
| `GET /api/products` is a 400 (`HTTP/2 403`) | tile.expert protects its pages with a Cloudflare challenge: a limitation of the project, not of the image. |
