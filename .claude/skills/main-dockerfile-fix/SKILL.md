---
name: main-dockerfile-fix
description: Create, check and fix the main (production) Dockerfile of this project and its prod stack - ./Dockerfile (php-base, vendor, app, nginx), docker-compose.prod.yaml, .env.prod.local.dist, .dockerignore, the prod php/nginx config and the Traefik labels - so the image builds and runs on the VPS behind Traefik and the Cloudflare Tunnel. Use it whenever the user wants to deploy to the VPS / prod server, asks to make, create, check, audit, fix, update or sync the main / root / prod Dockerfile or the production image, after changing the PHP or Node version, adding a PHP extension, a composer dependency, an env variable or a compose service, when a production docker build or start fails (missing extension, 404 from Traefik, 502, empty search, container exits at start), or when the image drifts from the dev setup - even if the user does not say "Dockerfile".
---

# main-dockerfile-fix

"Main Dockerfile" is `./Dockerfile`: the multi-stage production image (`php-base` → `vendor` → `app`, plus the `nginx` target) built on the server by `docker-compose.prod.yaml`. The dev images in `.docker/*/Dockerfile` and the dev compose files are **not** edited here; the dev image is the reference the production image is compared with, because developers add extensions and versions there first.

The reasons behind every item, the reference layout, the verification commands and the troubleshooting table are in [`docs/main-dockerfile-fix.md`](../../../docs/main-dockerfile-fix.md). The server side (Cloudflare, deploy key, secrets, first start, updates) is in [`docs/deploy.md`](../../../docs/deploy.md). Read the first one before changing anything.

## Run

```bash
bash .claude/skills/main-dockerfile-fix/scripts/check.sh        # project root = current dir
bash .claude/skills/main-dockerfile-fix/scripts/check.sh path/to/project
```

Each finding is printed as `RULE  location  message`, followed by a per-rule summary. `INFO` lines are things the script cannot decide (for example an extension that exists only in the dev image); they never fail the check. Exit code: `0` clean, `1` findings, `2` bad project root. It runs on the host and needs no container.

## Rules

| ID | Rule | How to fix |
|---|---|---|
| F1 | `ARG PHP_VERSION` satisfies `composer.json` `require.php` and equals the dev default in `docker-compose.yaml` | Change the ARG (keep the dev compose default in step if dev and prod should stay aligned). |
| F2 | Every `ext-*` required by `composer.json` / `composer.lock` is installed; the core set `zip opcache intl pdo_mysql bcmath` of the dev image is installed; `pdo_mysql` when the DSN is MySQL. Other dev-image extensions (gd, exif, xsl, amqp, redis, pdo_pgsql, excimer, pcov) are `INFO` only | Add it to the single `install-php-extensions` line in `php-base`. If the application starts to use an `INFO` extension, add it there too. |
| F4 | Every `COPY`/`ADD` source exists | Fix the path, or drop the line if the file was removed. |
| F5 | Every `target:` in the compose files is a stage of the Dockerfile; unused stages are listed as `INFO` | Rename the stage or the compose target (`app`, `nginx`). |
| F6 | `.dockerignore` exists and excludes `.git`, `vendor`, `var`, `.env.local`, `.env.prod.local`, `config/secrets/prod/prod.decrypt.private.php` | Add the missing lines: they keep secrets and hundreds of MB out of the build context and keep `COPY . .` cache-friendly. |
| F7 | `APP_ENV=prod` is set; the `app` stage ends as a non-root `USER`; `composer install` uses `--no-dev` | Restore the line. The cache warmup runs as `app` so `var/` is owned by it. |
| F9 | `docker-compose.prod.yaml` and `.env.prod.local.dist` agree: no `ports:` on `php-fpm`/`nginx`/`mysql`/`manticore`; nginx is in the `reverse-proxy` network with Traefik labels and no redirect/certresolver; `target: app` and `target: nginx`; every `${VAR}` without default is defined in the dist file; the dist file holds `!ChangeMe!` placeholders only; `.env.prod.local` is git-ignored | Fix the compose file or add the variable to the dist file. |

## Procedure

1. Run the script and read the whole output.
2. Triage. A finding that is not a real problem (for example a `COPY` source that a build step creates) is a false positive: list it separately and do not change the Dockerfile for it. Open `Dockerfile` and the related file (`composer.json`, compose files, `.docker/...`) to confirm each real finding before editing. If `./Dockerfile` does not exist, create it from the "Reference layout" in the doc.
3. Update `./Dockerfile`, `.dockerignore`, `docker-compose.prod.yaml` or `.env.prod.local.dist` with the smallest edit per finding. Keep the stage names (`php-base`, `vendor`, `app`, `nginx`) and the `app` default target, and keep the "copy manifests first, then sources" layering: it is tuned for layer caching on a small VPS. Never put real secrets into repo files.
4. Run the script again until it exits `0` (`INFO` lines may remain).
5. Verify the build. `docker build --check .` is cheap. Building both targets (`--target app`, `--target nginx`) runs composer and takes minutes: say so and run them (commands in the doc, section 5). If Docker is not available, say which checks were not run.
6. Report in chat: the findings, what changed in which file, what was skipped as a false positive, and whether the build was run. Do not commit. List the server-side steps still needed (`docs/deploy.md`) when the change affects them, for example a new variable that must be added to `.env.prod.local` on the server.

The script greps text, so it cannot see whether an extension is really used by the code, or whether a `COPY` of a directory contains everything a build step needs. Say so in the report when it matters.
