# tile-expert-test

Test task for Tile Expert: a small JSON REST API (Symfony) that

* reads a tile price from a [tile.expert](https://tile.expert) product page,
* creates orders and returns one order with its articles,
* returns the number of orders grouped by day / month / year (paginated) with a Manticore `*` wildcard search.

The task text is in [docs/technical-specifications-ru.md](docs/technical-specifications-ru.md),
the checklist of the work done is in [docs/technical-specifications.md](docs/technical-specifications.md).

## Stack

| Part | Technology |
|---|---|
| Language / framework | PHP 8.4, Symfony 8.1 |
| Database | MySQL 8, Doctrine ORM + Migrations |
| Search | Manticore Search (JSON API through Symfony HttpClient) |
| Async / CQRS | Symfony Messenger (command, query and event buses) |
| API docs | NelmioApiDocBundle (Swagger UI) |
| Runtime | Docker Compose: nginx, php-fpm, mysql, manticore, adminer |

## Quick start

```bash
make setup                    # containers + composer install + migrations + search index
```

`make setup` is the same as:

```bash
docker compose up --build -d
docker compose exec php-fpm composer install
docker compose exec php-fpm bin/console doctrine:migrations:migrate --no-interaction
docker compose exec php-fpm bin/console app:order:reindex
```

Then open:

| What | URL |
|---|---|
| API | http://localhost:8080/api/... |
| Swagger UI | http://localhost:8080/api/doc |
| Adminer (DB) | http://localhost:8081 (server `mysql`, user `user`, password `password`, db `tile_expert_test_backend`) |
| Manticore HTTP | http://localhost:9308 |

### Ports and settings

Published ports are environment variables (see `.env`, override them in `.env.local` or the shell):

| Variable | Default | Service |
|---|---|---|
| `NGINX_PORT` | `8080` | application |
| `MYSQL_PORT` | `3306` | MySQL |
| `MANTICORE_PORT` | `9308` | Manticore HTTP API |
| `ADMINER_PORT` | `8081` | Adminer |

Settings can be overridden in `.env.local` (git-ignored). Other variables: `DATABASE_URL`, `MANTICORE_URL`, `MANTICORE_ORDERS_TABLE`,
`PRODUCT_PAGE_URL_TEMPLATE`, `PRODUCT_PRICE_CACHE_TTL` (seconds).

`docker-compose.override.yaml.dist` shows local-only additions (e.g. an nginx reverse proxy);
copy it to `docker-compose.override.yaml` (git-ignored) if you need them.

### Make targets

Run `make` or `make help` to print the list. All targets run from the project root; the PHP ones execute inside the
`php-fpm` container (`docker compose exec`), so the containers must be up.

**Docker**

| Target | Description |
|---|---|
| `make docker-build` | build all Docker images (`docker compose build`) |
| `make docker-up` | build the images if needed and start all containers in the background |
| `make docker-stop` | stop the containers without removing them (data and volumes are kept) |
| `make docker-down` | stop and remove the containers (named volumes such as the Manticore index are kept) |
| `make docker-restart` | restart all containers |
| `make docker-ps` | show the status and published ports of the containers |
| `make docker-logs` | follow the logs of all containers |

**Shells and logs**

| Target | Description |
|---|---|
| `make php-console` | open an interactive bash shell in the `php-fpm` container (run `bin/console`, `composer`, ...) |
| `make mysql-console` | open the MySQL client in the `mysql` container as `root` |
| `make php-logs` | follow the `php-fpm` logs |
| `make nginx-logs` | follow the `nginx` logs |
| `make mysql-logs` | follow the `mysql` logs |
| `make manticore-logs` | follow the `manticore` logs |

**Backend (PHP / Symfony)**

| Target | Description |
|---|---|
| `make setup` | first start in one command: `docker-up`, `install`, `migrate`, `migrate-test` and `search-reindex` |
| `make install` | install the PHP dependencies (`composer install`) |
| `make update` | update the PHP dependencies and `composer.lock` (`composer update`) |
| `make migrate` | run the Doctrine migrations on the development database |
| `make migrate-test` | create the test database `tile_expert_test_backend_test` (if missing) and run the migrations on it |
| `make cache-clear` | clear the Symfony cache |
| `make search-reindex` | drop and rebuild the Manticore `orders` index from MySQL (`app:order:reindex`) |
| `make api-doc` | print the OpenAPI specification as JSON (the Swagger UI itself is at `/api/doc`) |

**Tests and code style**

| Target | Description |
|---|---|
| `make test` | run the PHPUnit suite (needs `make migrate-test` once and the MySQL and Manticore containers running) |
| `make lint` | check the code style with PHP CS Fixer (`composer run lint`) without changing files |
| `make lint-fix` | fix the code style (`composer run lint:fix`) |

**Production (VPS)** — see [docs/deploy.md](docs/deploy.md); they use `docker-compose.prod.yaml` and `.env.prod.local`

| Target | Description |
|---|---|
| `make prod-deploy` | run `bin/deploy.sh` on the server: git pull, database backup, build, restart, checks |
| `make prod-deploy-remote` | deploy from this machine: `bin/deploy.sh --remote` runs the deploy on the server over ssh (push your commits first) |
| `make prod-build` | build the production images (`app` and `nginx` targets of the root `Dockerfile`) |
| `make prod-up` | build and (re)start the production stack in the background |
| `make prod-down` | stop and remove the production containers (the `mysql` and `manticore` volumes are kept) |
| `make prod-ps` | show the status of the production containers |
| `make prod-logs` | follow the production logs |
| `make prod-console` | open a bash shell in the production `php-fpm` container |
| `make prod-reindex` | rebuild the Manticore index in the production stack (`app:order:reindex`) |

**Help**

| Target | Description |
|---|---|
| `make help` | show the list of targets with their descriptions (the default target) |

## API

Base URL: `http://localhost:8080`. All responses are JSON. The same description is available
interactively in Swagger UI (`/api/doc`); ready-to-run requests (PhpStorm / VS Code REST Client `.http` files) are in
[tests/_https/orders](tests/_https/orders): `post.orders.http` (create), `post.orders.invalid.http` (validation errors),
`get.orders.collection.http` (grouping, pagination, filters), `get.orders.search.http` (Manticore search),
`get.orders.details.http` / `get.orders.http` (one order). The host is set in the `@host` variable at the top of each file.

Errors have one format:

```json
{"code": 422, "message": "Validation Failed", "errors": {"fields": {"email": "This value is not a valid email address."}}}
```

| Status | Meaning |
|---|---|
| 400 | invalid JSON / bad request, or the upstream site failed (products) |
| 404 | entity not found |
| 422 | validation failed |

### 1. Get a product price

`GET /api/products?factory={factory}&collection={collection}&article={article}`

Reads the EUR price from `https://tile.expert/it/tile/{factory}/{collection}/a/{article}`.
Successful lookups are cached for `PRODUCT_PRICE_CACHE_TTL` seconds.

| Query parameter | Required | Description |
|---|---|---|
| `factory` | yes | factory slug, e.g. `marca-corona` |
| `collection` | yes | collection slug, e.g. `arteseta` |
| `article` | yes | article slug, e.g. `k263-arteseta-camoscio-s000628660` |

```bash
curl "http://localhost:8080/api/products?factory=marca-corona&collection=arteseta&article=k263-arteseta-camoscio-s000628660"
```

```json
{"price": 59.99, "factory": "marca-corona", "collection": "arteseta", "article": "k263-arteseta-camoscio-s000628660"}
```

| Status | When |
|---|---|
| 200 | price found |
| 400 | tile.expert is unavailable or refused the request |
| 404 | product page or price not found |
| 422 | a parameter is missing or is not a valid slug |

> tile.expert protects its pages with a Cloudflare challenge, so server-side requests can be answered with `403`
> (reported as `400`). The price parser (JSON-LD, Open Graph, microdata) has not been verified against the live page.

### 2. Get orders grouped by period (paginated)

`GET /api/orders`

Returns the number of orders per day, month or year (by `create_date`), paginated by groups.

| Query parameter | Default | Description |
|---|---|---|
| `group_by` | `day` | `day`, `month` or `year` |
| `page` | `1` | page number (>= 1) |
| `per_page` | `10` | groups per page (1..1000) |
| `start_day` | – | `yyyy-mm-dd`, orders created on or after this day |
| `end_day` | – | `yyyy-mm-dd`, orders created on or before this day |
| `sort` | `group desc` | `group asc\|desc` or `count asc\|desc` |
| `query` | – | Manticore wildcard search (see below) |

**Search (`query`).** Searches the order hash, email, name, number, client name and surname, company name and
description in Manticore. `foo` matches `*foo*`; your own `*` wildcards are kept (`foo*`, `*@example.com`);
several words must all match; `*` or an empty value means no filter. If Manticore is unavailable the search falls back
to MySQL `LIKE`. At most 10 000 matching orders are taken into account.

```bash
curl "http://localhost:8080/api/orders?group_by=month&per_page=12&query=example*"
```

```json
{
  "items": [{"group": "2026-11", "count": 20}],
  "meta": {
    "paginator": {"pages": 1, "page": 1, "per_page": 12, "total": 1},
    "filters": {
      "query": "example*",
      "sort": "group desc",
      "available_sort_by": ["group", "count"],
      "available_filters": {"query": true, "group_by": ["day", "month", "year"], "start_day": true, "end_day": true},
      "applied_filters": {"query": "example*", "group_by": "month", "start_day": null, "end_day": null}
    }
  }
}
```

`group` is `Y-m-d`, `Y-m` or `Y` depending on `group_by`; `total` is the number of groups, `pages` the number of pages.

| Status | When |
|---|---|
| 200 | ok |
| 422 | invalid parameter (e.g. `group_by=week`, `page=0`) |

### 3. Create an order

`POST /api/orders` — `Content-Type: application/json`

Creates the order **together with its articles** in one transaction: if the order or any article is invalid nothing is saved.

| Field | Required | Rules |
|---|---|---|
| `hash` | yes | string of 32 characters, unique |
| `token` | yes | string of 64 characters |
| `email` | yes | valid email, max 100 |
| `vat_type` | yes | `vat-type-1`, `vat-type-2`, `vat-type-3` |
| `vat_number` | no | max 100 |
| `tax_number` | no | max 50 |
| `payment_type` | yes | `visa`, `mastercard`, `paypal` |
| `locale` | yes | 5 characters, e.g. `en_GB` |
| `currency` | yes | `EUR`, `USD` |
| `measure` | yes | `m`, `mm`, `sm`, `kg` |
| `name` | yes | max 200 |
| `articles` | yes | array of articles, 1..200 items (see below) |

Each item of `articles`:

| Field | Required | Rules |
|---|---|---|
| `article_id` | yes | integer > 0 (collection id) |
| `amount` | yes | number > 0, quantity in the unit of measure |
| `price` | yes | number >= 0, price at the time of the order |
| `price_eur` | no | number >= 0 |
| `currency` | no | `EUR`, `USD` |
| `measure` | no | `m`, `mm`, `sm`, `kg` |
| `delivery_time_min` | no | `yyyy-mm-dd` |
| `delivery_time_max` | no | `yyyy-mm-dd`, not earlier than `delivery_time_min` |
| `weight` | yes | number >= 0, package weight |
| `multiple_pallet` | no | `1` - multiple of a package, `2` - of a pallet, `3` - not less than a pallet |
| `packaging_count` | yes | number > 0, the quantity the product can be added in |
| `pallet` | yes | number >= 0, quantity per pallet |
| `packaging` | yes | number > 0, quantity per package |
| `swimming_pool` | no | boolean, default `false` |

```bash
curl -X POST http://localhost:8080/api/orders -H 'Content-Type: application/json' -d '{
  "hash": "12345678901234567890123456780000",
  "token": "1234567890123456789012345678901212345678901234567890123456789000",
  "email": "test@example.com",
  "vat_type": "vat-type-1",
  "vat_number": "0123456789",
  "tax_number": "9876543210",
  "payment_type": "visa",
  "locale": "en_GB",
  "currency": "EUR",
  "measure": "m",
  "name": "Order name",
  "articles": [
    {
      "article_id": 5,
      "amount": 12.5,
      "price": 30.2,
      "price_eur": 30.2,
      "currency": "EUR",
      "measure": "m",
      "delivery_time_min": "2026-12-01",
      "delivery_time_max": "2026-12-15",
      "weight": 20,
      "multiple_pallet": 1,
      "packaging_count": 1,
      "pallet": 50,
      "packaging": 1.44,
      "swimming_pool": false
    }
  ]
}'
```

| Status | When |
|---|---|
| 204 | order and its articles created (no body); the order is added to the search index |
| 400 | invalid JSON, or a value of a wrong type (e.g. `articles` is a string) |
| 422 | validation failed; every failed field is listed in `errors.fields`, article errors use the path of the item, e.g. `articles[1].amount` |

```json
{"code": 422, "message": "Validation Failed", "errors": {"fields": {"articles[1].amount": "This value should be positive."}}}
```

Internally the controller dispatches `CreateOrderCommand`; `CreateOrderHandler` picks an entity factory that
supports the data source (`CreateOrderRequest`, an HTTP request or an array), saves the order (the articles are saved by
the cascade of the `Order::$articles` association) and dispatches `OrderCreatedEvent`. When no factory supports the source
it throws `UnprocessableEntityException`.

### 4. Get an order

`GET /api/orders/{id}` — `id` is a positive integer.

```bash
curl http://localhost:8080/api/orders/1
```

```json
{
  "id": 1,
  "hash": "12345678901234567890123456789012",
  "token": "1234567890123456789012345678901212345678901234567890123456789012",
  "email": "test@example.com",
  "vat_type": "vat-type-1",
  "vat_number": "0123456789",
  "tax_number": "9876543210",
  "payment_type": "visa",
  "locale": "en_GB",
  "currency": "EUR",
  "measure": "m",
  "name": "name",
  "created_at": "2026-11-22T06:18:12+00:00",
  "updated_at": null,
  "articles": [
    {
      "id": 1, "article_id": 5, "amount": 12.5, "price": 30.2, "price_eur": 30.2,
      "currency": "EUR", "measure": "m", "delivery_time_min": null, "delivery_time_max": null,
      "weight": 20, "multiple_pallet": null, "packaging_count": 1, "pallet": 50, "packaging": 1.44,
      "swimming_pool": false
    }
  ]
}
```

| Status | When |
|---|---|
| 200 | order found |
| 404 | `{"code": 404, "message": "Order Not Found"}` |

## Deployment (production VPS)

The development stack (`docker-compose.yaml`, code bind-mounted, dev passwords) is **not** used in production.
Production is a separate stack built from the root `Dockerfile`:

| File | Purpose |
|---|---|
| `Dockerfile` | multi-stage production image: `php-base` → `vendor` → `app` (php-fpm, code baked in, no dev packages, non-root) and the `nginx` target |
| `docker-compose.prod.yaml` | `php-fpm`, `nginx`, `mysql`, `manticore`; **no published ports**, nginx joins the `reverse-proxy` network with Traefik labels |
| `.env.prod.local.dist` | template of the server-only `.env.prod.local` (secrets, domain; placeholders only) |
| `.dockerignore` | keeps secrets, `vendor`, `var`, tests and docs out of the image |
| `bin/deploy.sh` | the deploy script: run it on the server, or from your machine with `--remote` |
| `.claude/skills/main-dockerfile-fix/` | Claude skill (+ `scripts/check.sh`) that creates, checks and fixes the files above |

On the VPS the stack runs behind the shared Traefik and a Cloudflare Tunnel at `https://tile-expert-test.lucked.me`.

```bash
# on the server, in /home/dad/workspace/www/tile-expert-test (first start: docs/deploy.md, sections 3-6)
bin/deploy.sh                      # pull, backup, build, restart, health checks
bin/deploy.sh --reindex            # ...and rebuild the Manticore search index

# from your machine, after `git push`
bin/deploy.sh --remote             # runs the same script on the server over ssh (DEPLOY_HOST, DEPLOY_DIR)
bin/deploy.sh --remote --ref <tag|commit>   # deploy a revision / roll back
```

Options: `--ref`, `--no-pull`, `--no-prune`, `--no-backup`, `--reindex`, `--remote`, `--host`, `--dir`, `--help`.

* **[docs/deploy.md](docs/deploy.md)** — the full guide: Cloudflare hostname, deploy key and clone, `.env.prod.local`, first start,
  verification, updates and rollback with `bin/deploy.sh`, backups, troubleshooting;
* **[docs/main-dockerfile-fix.md](docs/main-dockerfile-fix.md)** — how the production `Dockerfile` and compose file are built and kept in sync
  (used by the `main-dockerfile-fix` skill; run `bash .claude/skills/main-dockerfile-fix/scripts/check.sh` to audit them).

## Project structure

```
src/
  Controller/Api/{Order,Product}   thin controllers: request -> message bus -> JSON
  ControllerRequest/               request DTOs (validated, bound by ControllerRequestResolver)
  Message/{Command,Query,Event}    messages
  MessageHandler/                  handlers (one per message)
  EntityFactory/Order              entity factories, picked by the data source (tag app.order.entity_factory)
  Service/{Manticore,Order,Product} Manticore client, search + indexer, product price provider/parser
  Repository/                      Doctrine repositories (grouped order counts use DBAL)
  DTO/, DTOFactory/                response DTOs and their factories
  Command/                         console commands (app:order:reindex)
migrations/                        Doctrine migrations
docs/                              task, checklist plan, database notes, deployment guides
tests/_https/                      ready-to-run HTTP requests
bin/deploy.sh                      production deploy script (--remote deploys from your machine)
Dockerfile, docker-compose.prod.yaml, .env.prod.local.dist   production image and stack
.claude/skills/                    Claude skills (fix-migrations, main-dockerfile-fix)
```

Request flow: `Controller -> MessageBusHandler -> Handler -> Repository / Service -> DTO -> JSON`.

## Tests

```bash
make migrate-test   # once: creates tile_expert_test_backend_test and runs the migrations (also part of make setup)
make test           # php bin/phpunit inside the php-fpm container
```

The functional tests need the compose services (MySQL and Manticore) to be running. They use the separate
`tile_expert_test_backend_test` database and the `orders_test` Manticore table, so the development data is untouched;
every test starts from empty tables. The tile.expert HTTP client is replaced by `MockHttpClient`, no real requests are made.

| Suite | What it covers |
|---|---|
| `tests/Functional/Api/Order/CreateOrderTest` | `POST /api/orders`: success, articles saved (and returned by `GET /api/orders/{id}`), articles required / non-empty / array, per-article validation, nothing saved when an article is invalid, new order is searchable, 422 validation, unknown enum, duplicate hash, 400 bad JSON |
| `tests/Functional/Api/Order/GetOrderCollectionTest` | `GET /api/orders`: grouping by day/month/year, pagination, sort, date filter, Manticore wildcard search, validation |
| `tests/Functional/Api/Order/GetOrderDetailsTest` | `GET /api/orders/{id}`: order with / without articles, 404 |
| `tests/Functional/Api/Product/GetProductTest` | `GET /api/products`: price from JSON-LD / meta, upstream URL, 404, upstream failure, validation |
| `tests/Functional/MessageHandler/CreateOrderHandlerTest` | `CreateOrderHandler`: factory per source (request DTO, array, HTTP request) incl. articles, `UnprocessableEntityException` without a factory |
| `tests/Unit/...` | price parser, price provider (cache), search query builder / client, `MessageBusHandler` |

## Documentation

* [docs/technical-specifications.md](docs/technical-specifications.md) — checklist plan of the work (progress);
* [docs/technical-specifications-ru.md](docs/technical-specifications-ru.md) — original task;
* [docs/db-dump-analysis.md](docs/db-dump-analysis.md) — what is wrong in the provided database dump and how it was improved;
* [docs/dump.original.sql](docs/dump.original.sql) / [docs/dump.improved.sql](docs/dump.improved.sql) — the provided and the improved schema;
* [docs/deploy.md](docs/deploy.md) — deploying to the production VPS (`bin/deploy.sh`, `--remote`);
* [docs/main-dockerfile-fix.md](docs/main-dockerfile-fix.md) — the production `Dockerfile`: layout, checklist, verification.
