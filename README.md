# Tile Expert test task

**Backend**
&nbsp;[![PHP 8.4](https://img.shields.io/badge/PHP-8.4-777BB4?style=flat-square&logo=php&logoColor=white)](https://www.php.net)
[![Symfony 8.1](https://img.shields.io/badge/Symfony-8.1-000000?style=flat-square&logo=symfony&logoColor=white)](https://symfony.com)
[![Doctrine ORM 3.8](https://img.shields.io/badge/Doctrine_ORM-3.8-FC6A31?style=flat-square&logo=doctrine&logoColor=white)](https://www.doctrine-project.org)
[![OpenAPI / Swagger UI](https://img.shields.io/badge/OpenAPI-Swagger_UI-85EA2D?style=flat-square&logo=swagger&logoColor=black)](https://tile-expert-test.lucked.me/api/doc)

**Infrastructure**
&nbsp;[![MySQL 8.0](https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat-square&logo=mysql&logoColor=white)](https://www.mysql.com)
[![Manticore Search 32.1](https://img.shields.io/badge/Manticore_Search-32.1-00A5A8?style=flat-square)](https://manticoresearch.com)
[![nginx](https://img.shields.io/badge/nginx-mainline-009639?style=flat-square&logo=nginx&logoColor=white)](https://nginx.org)
[![Docker Compose](https://img.shields.io/badge/Docker-Compose-2496ED?style=flat-square&logo=docker&logoColor=white)](https://docs.docker.com/compose/)

**Tooling**
&nbsp;[![PHPUnit 13.4](https://img.shields.io/badge/PHPUnit-13.4-366488?style=flat-square&logo=php&logoColor=white)](https://phpunit.de)
[![PHP CS Fixer 3.95](https://img.shields.io/badge/PHP_CS_Fixer-3.95-FF6F00?style=flat-square)](https://cs.symfony.com)
[![Composer](https://img.shields.io/badge/Composer-2-885630?style=flat-square&logo=composer&logoColor=white)](https://getcomposer.org)

A small JSON REST API built with Symfony. It:

- reads a tile price from a [tile.expert](https://tile.expert) product page;
- creates orders together with their articles, and returns an order by id;
- counts orders grouped by day, month or year (paginated), with Manticore `*` wildcard search.

**Live demo:** <https://tile-expert-test.lucked.me/api/doc> (Swagger UI on the production VPS)

## Contents

- [Features](#features)
- [Tech stack](#tech-stack)
- [Getting started](#getting-started)
- [Configuration](#configuration)
- [Usage](#usage)
- [API reference](#api-reference)
- [Architecture](#architecture)
- [Testing and code style](#testing-and-code-style)
- [Deployment](#deployment)
- [Troubleshooting](#troubleshooting)
- [Documentation](#documentation)
- [License](#license)

## Features

- Create an order with up to 200 articles in a single transaction; invalid data saves nothing.
- Count orders grouped by day, month or year, with pagination, sorting and a date range.
- Wildcard search over orders through Manticore, with a MySQL `LIKE` fallback when Manticore is down.
- Read a tile price from a tile.expert product page (cached).
- Uniform JSON error format and a Swagger UI generated from the code.
- Separate production stack behind Traefik and a Cloudflare Tunnel, deployed with one script.

## Tech stack

| Layer | Technology | Version | Purpose |
|---|---|---|---|
| Language | PHP | 8.4 | runtime (`php-fpm`) |
| Framework | Symfony | 8.1 | HTTP, validation, serialization, Messenger, HttpClient |
| ORM | Doctrine ORM and Migrations | 3.8 / 4.0 | orders and articles, schema migrations |
| Database | MySQL | 8.0 | primary storage |
| Search | Manticore Search | 32.1.3 | wildcard order search (JSON API through HttpClient) |
| API docs | NelmioApiDocBundle | 5.14 | OpenAPI and Swagger UI |
| Web server | nginx | mainline | reverse proxy to php-fpm |
| Runtime | Docker Compose | - | nginx, php-fpm, mysql, manticore, adminer (dev only) |
| Tests | PHPUnit | 13.4 | unit and functional tests |
| Code style | PHP CS Fixer | 3.95 | Symfony ruleset |

## Getting started

### Prerequisites

- Docker with the Compose plugin
- GNU Make
- Free ports `8080`, `8081`, `3306` and `9308` (or [change them](#configuration))

### Install and run

```bash
git clone git@github.com:LuckedMe/tile-expert-test.git
cd tile-expert-test
make setup
```

`make setup` starts the containers, runs `composer install`, applies the migrations to the development and test
databases, and builds the search index. To do it by hand:

```bash
docker compose up --build -d
docker compose exec php-fpm composer install
docker compose exec php-fpm bin/console doctrine:migrations:migrate --no-interaction
docker compose exec php-fpm bin/console app:order:reindex
```

### Verify

```bash
curl -i "http://localhost:8080/api/orders?group_by=month"   # 200 with {"items": [], "meta": {...}} on an empty database
```

| What | URL |
|---|---|
| API | <http://localhost:8080/api> |
| Swagger UI | <http://localhost:8080/api/doc> |
| Adminer (database UI) | <http://localhost:8081> (server `mysql`, user `user`, password `password`, database `tile_expert_test_backend`) |
| Manticore HTTP | <http://localhost:9308> |

The credentials above are for the local development stack only.

## Configuration

`.env` is committed and holds defaults only. Override values in `.env.local` (git-ignored) or in the shell.
Real secrets never go into committed files.

| Variable | Default | Purpose |
|---|---|---|
| `NGINX_PORT` | `8080` | published port of the application |
| `ADMINER_PORT` | `8081` | published port of Adminer |
| `MYSQL_PORT` | `3306` | published port of MySQL |
| `MANTICORE_PORT` | `9308` | published port of the Manticore HTTP API |
| `DATABASE_URL` | see `.env` | Doctrine connection |
| `MANTICORE_URL` | see `.env` | Manticore HTTP endpoint |
| `MANTICORE_ORDERS_TABLE` | `orders` | name of the search index table |
| `PRODUCT_PAGE_URL_TEMPLATE` | see `.env` | tile.expert product page URL template |
| `PRODUCT_PRICE_CACHE_TTL` | see `.env` | how long a found price is cached, in seconds |

For local-only additions (for example an nginx reverse proxy) copy `docker-compose.override.yaml.dist` to
`docker-compose.override.yaml` (git-ignored).

## Usage

Run `make` or `make help` to list every target. All targets run from the project root. The PHP ones execute inside
the `php-fpm` container, so the containers must be up.

| Task | Command |
|---|---|
| First start | `make setup` |
| Start / stop / remove containers | `make docker-up` / `make docker-stop` / `make docker-down` |
| Container status and logs | `make docker-ps`, `make docker-logs` (also `php-logs`, `nginx-logs`, `mysql-logs`, `manticore-logs`) |
| Shell in PHP / MySQL container | `make php-console` / `make mysql-console` |
| Install / update dependencies | `make install` / `make update` |
| Run migrations (dev / test database) | `make migrate` / `make migrate-test` |
| Rebuild the search index | `make search-reindex` |
| Clear the Symfony cache | `make cache-clear` |
| Print the OpenAPI specification | `make api-doc` |
| Run tests | `make test` |
| Check / fix code style | `make lint` / `make lint-fix` |
| Production (VPS) | `make prod-*`, see [Deployment](#deployment) |

## API reference

Base URL: `http://localhost:8080`. All responses are JSON. The full, interactive description is in Swagger UI
(`/api/doc`). Ready-to-run requests (PhpStorm / VS Code REST Client) are in [`tests/_https/orders`](tests/_https/orders);
the host is set in the `@host` variable at the top of each file.

| Method and path | Purpose |
|---|---|
| `GET /api/products` | [price of a tile](#get-apiproducts) |
| `GET /api/orders` | [order counts grouped by period](#get-apiorders) |
| `POST /api/orders` | [create an order with articles](#post-apiorders) |
| `GET /api/orders/{id}` | [one order with its articles](#get-apiordersid) |

### Errors

Every error has the same shape:

```json
{"code": 422, "message": "Validation Failed", "errors": {"fields": {"email": "This value is not a valid email address."}}}
```

| Status | Meaning |
|---|---|
| 400 | invalid JSON or a value of the wrong type; upstream site failure (products) |
| 404 | entity not found |
| 422 | validation failed; failed fields are listed in `errors.fields` |

### GET /api/products

Reads the EUR price from `https://tile.expert/it/tile/{factory}/{collection}/a/{article}`. Successful lookups are
cached for `PRODUCT_PRICE_CACHE_TTL` seconds.

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

> **Known limitation:** tile.expert protects its pages with a Cloudflare challenge, so server-side requests can get
> a `403` (reported as `400`). The price parser (JSON-LD, Open Graph, microdata) has not been verified against the live page.

### GET /api/orders

Returns the number of orders per day, month or year (by `create_date`), paginated by groups.

| Query parameter | Default | Description |
|---|---|---|
| `group_by` | `day` | `day`, `month` or `year` |
| `page` | `1` | page number, >= 1 |
| `per_page` | `10` | groups per page, 1..1000 |
| `start_day` | none | `yyyy-mm-dd`, orders created on or after this day |
| `end_day` | none | `yyyy-mm-dd`, orders created on or before this day |
| `sort` | `group desc` | `group asc\|desc` or `count asc\|desc` |
| `query` | none | Manticore wildcard search, see below |

**Search.** `query` searches the order hash, email, name, number, client name and surname, company name and
description. `foo` matches `*foo*`; your own `*` wildcards are kept (`foo*`, `*@example.com`); several words must
all match; `*` or an empty value means no filter. If Manticore is unavailable, the search falls back to MySQL `LIKE`.
At most 10 000 matching orders are taken into account.

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

`group` is `Y-m-d`, `Y-m` or `Y` depending on `group_by`. `total` is the number of groups and `pages` the number of pages.

| Status | When |
|---|---|
| 200 | ok |
| 422 | invalid parameter, e.g. `group_by=week` or `page=0` |

### POST /api/orders

`Content-Type: application/json`. Creates the order **together with its articles** in one transaction: if the order
or any article is invalid, nothing is saved.

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
| `articles` | yes | array of 1..200 articles |

Each article:

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
| `multiple_pallet` | no | `1` multiple of a package, `2` of a pallet, `3` not less than a pallet |
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
      "weight": 20,
      "packaging_count": 1,
      "pallet": 50,
      "packaging": 1.44
    }
  ]
}'
```

| Status | When |
|---|---|
| 204 | order and articles created (no body); the order is added to the search index |
| 400 | invalid JSON, or a value of the wrong type (e.g. `articles` is a string) |
| 422 | validation failed; article errors use the item path, e.g. `articles[1].amount` |

### GET /api/orders/{id}

`id` is a positive integer.

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

## Architecture

Request flow: `Controller -> MessageBusHandler -> Handler -> Repository / Service -> DTO -> JSON`.

Controllers are thin: they bind and validate the request into a DTO, dispatch a command or query on the Messenger
bus, and return the resulting DTO as JSON. For `POST /api/orders`, `CreateOrderHandler` picks an entity factory that
supports the data source (`CreateOrderRequest`, an HTTP request or an array), saves the order (articles are saved by
the cascade of `Order::$articles`) and dispatches `OrderCreatedEvent`, which updates the search index.

```
src/
  Controller/Api/{Order,Product}    thin controllers
  ControllerRequest/                request DTOs, bound and validated by ControllerRequestResolver
  Message/{Command,Query,Event}     messages
  MessageHandler/                   one handler per message
  EntityFactory/Order               entity factories, tagged app.order.entity_factory
  Service/{Manticore,Order,Product} Manticore client, search and indexer, product price provider and parser
  Repository/                       Doctrine repositories (grouped counts use DBAL)
  DTO/, DTOFactory/                 response DTOs and their factories
  Command/                          console commands (app:order:reindex)
migrations/                         Doctrine migrations
tests/                              Unit, Functional and ready-to-run HTTP requests (_https)
docs/                               task, plan, database notes, deployment guides
bin/deploy.sh                       production deploy script
```

## Testing and code style

```bash
make migrate-test   # once: creates tile_expert_test_backend_test and runs the migrations (part of make setup)
make test           # php bin/phpunit inside the php-fpm container
make lint           # PHP CS Fixer, check only
make lint-fix       # PHP CS Fixer, fix
```

Functional tests need the MySQL and Manticore containers running. They use the separate
`tile_expert_test_backend_test` database and the `orders_test` Manticore table, so development data is untouched, and
every test starts from empty tables. The tile.expert HTTP client is replaced by `MockHttpClient`, so no real
requests are made.

| Suite | Covers |
|---|---|
| `tests/Functional/Api/Order/CreateOrderTest` | `POST /api/orders`: success, articles, validation, atomicity, search indexing, duplicate hash, bad JSON |
| `tests/Functional/Api/Order/GetOrderCollectionTest` | `GET /api/orders`: grouping, pagination, sort, date filter, wildcard search, validation |
| `tests/Functional/Api/Order/GetOrderDetailsTest` | `GET /api/orders/{id}`: with and without articles, 404 |
| `tests/Functional/Api/Product/GetProductTest` | `GET /api/products`: price parsing, upstream URL, 404, upstream failure, validation |
| `tests/Functional/MessageHandler/CreateOrderHandlerTest` | `CreateOrderHandler`: factory per data source, no-factory error |
| `tests/Unit/...` | price parser and provider (cache), search query builder and client, `MessageBusHandler` |

Code style is the Symfony standard enforced by PHP CS Fixer (`.php-cs-fixer.dist.php`).

## Deployment

The development stack (`docker-compose.yaml`: bind-mounted code, dev passwords) is **not** used in production.
Production is a separate stack built from the root `Dockerfile` and run behind a shared Traefik and a Cloudflare
Tunnel at <https://tile-expert-test.lucked.me>.

| File | Purpose |
|---|---|
| `Dockerfile` | multi-stage image: `php-base`, `vendor`, `app` (php-fpm, code baked in, no dev packages, non-root) and `nginx` |
| `docker-compose.prod.yaml` | `php-fpm`, `nginx`, `mysql`, `manticore`; no published ports, nginx joins the `reverse-proxy` network with Traefik labels |
| `.env.prod.local.dist` | template of the server-only `.env.prod.local` (placeholders only) |
| `.dockerignore` | keeps secrets, `vendor`, `var`, tests and docs out of the image |
| `bin/deploy.sh` | deploy script, run on the server or from your machine with `--remote` |

```bash
# on the server (first start: docs/deploy.md)
bin/deploy.sh                                # pull, backup, build, restart, health checks
bin/deploy.sh --reindex                      # ...and rebuild the search index

# from your machine, after `git push`
bin/deploy.sh --remote                       # same script over ssh (DEPLOY_HOST, DEPLOY_DIR)
bin/deploy.sh --remote --ref <tag|commit>    # deploy a revision or roll back
```

Options: `--ref`, `--no-pull`, `--no-prune`, `--no-backup`, `--reindex`, `--remote`, `--host`, `--dir`, `--help`.
Make shortcuts: `prod-deploy`, `prod-deploy-remote`, `prod-build`, `prod-up`, `prod-down`, `prod-ps`, `prod-logs`,
`prod-console`, `prod-reindex`.

## Troubleshooting

| Symptom | Cause / fix |
|---|---|
| `make setup` fails on a busy port | change `NGINX_PORT`, `ADMINER_PORT`, `MYSQL_PORT` or `MANTICORE_PORT` in `.env.local` |
| Search returns nothing | the index is empty: `make search-reindex` (without Manticore the API falls back to `LIKE`) |
| Functional tests fail with a database error | the test database is missing: `make migrate-test` |
| `POST /api/orders` is `422` with `hash ... already exist` | `hash` must be unique; send another one |
| `GET /api/products` is `400` | tile.expert answered with a Cloudflare challenge (`403`); a known limitation, see [GET /api/products](#get-apiproducts) |

Production problems (Traefik `404`/`502`, build killed, deploy script errors) are listed in
[docs/deploy.md](docs/deploy.md#11-troubleshooting).

## Documentation

- [docs/technical-specifications-ru.md](docs/technical-specifications-ru.md): the original task
- [docs/technical-specifications.md](docs/technical-specifications.md): checklist of the work done
- [docs/db-dump-analysis.md](docs/db-dump-analysis.md): problems in the provided database dump and how they were fixed
- [docs/dump.original.sql](docs/dump.original.sql), [docs/dump.improved.sql](docs/dump.improved.sql): provided and improved schema
- [docs/deploy.md](docs/deploy.md): full production guide (Cloudflare, deploy key, first start, updates, rollback, backups, troubleshooting)
- [docs/main-dockerfile-fix.md](docs/main-dockerfile-fix.md): how the production `Dockerfile` and compose file are built and kept in sync

## License

Proprietary (see `composer.json`). This is a test task and is not licensed for reuse.
