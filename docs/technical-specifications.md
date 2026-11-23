# Technical specifications — implementation plan

Tick a box (`- [x]`) when a step is done. Original task text (RU): [technical-specifications-ru.md](technical-specifications-ru.md).

## 1. Decisions

- Product endpoint takes query params: `GET /api/products?factory=&collection=&article=` (replaces the `/api/products/{id}` stub).
- Create order is JSON REST only (no SOAP), as hinted in the task ("2? see REST API").
- Ports are configured through environment variables.

## 2. Endpoints

- [ ] `GET /api/products?factory=&collection=&article=`
  - [x] fetch `https://tile.expert/it/tile/{factory}/{collection}/a/{article}` with Symfony HttpClient
  - [ ] parse the EUR price from the page (parser written for JSON-LD / meta / microdata, **not verified against the live page**: tile.expert answers 403 with a Cloudflare challenge to server-side requests)
  - [x] respond `{"price": 59.99, "factory": "...", "collection": "...", "article": "..."}`
  - [x] 404 when the page or price is not found; cache successful lookups (Symfony Cache)
- [x] `GET /api/orders` — paginated and grouped
  - [x] params: `page`, `per_page`, `group_by` (day | month | year), `start_day`, `end_day`, `sort`, `query`
  - [x] response: page, per page, total, pages + `data: [{group, count}]`
- [x] `POST /api/orders`
  - [x] `CreateOrderHandler` picks an entity factory by source (HTTP request, `CreateOrderRequest`)
  - [x] `UnprocessableEntityException` when no factory supports the source
  - [x] remove the duplicate `OrderCreateRequestEntityFactory`; make `OrderHttpRequestEntityFactory` return a real order
  - [x] dispatch `OrderCreatedEvent`
  - [x] `articles` array in the request body (`CreateOrderArticleRequest`, 1..200 items, validated per item)
  - [x] save the `OrderArticle` rows with the order in one transaction (cascade persist); nothing is saved when an article is invalid
  - [x] validation errors of the items use the item path (`articles[1].amount`); a value of a wrong type returns 400, not 500
- [x] `GET /api/orders/{id}` — order details with articles; 404 when missing

## 3. Additional

- [x] Manticore search
  - [x] add the Manticore service to `docker-compose.yaml`
  - [x] `orders` index (`app:order:reindex`) + new orders indexed on `OrderCreatedEvent`
  - [x] client built on Symfony HttpClient (Manticore JSON API) instead of a third-party package, per `AGENTS.md`
  - [x] `query` param of `GET /api/orders` uses the Manticore `*` wildcard search; Manticore returns order ids, MySQL does filtering and grouping
  - [x] graceful fallback to MySQL `LIKE` when Manticore is unavailable
- [x] OpenAPI (Swagger) description of all four endpoints (UI: `/api/doc`)
- [x] ports in `.env` / `docker-compose.yaml`, `makefile` targets
- [x] main `README.md` about the project
  - [x] purpose, stack (PHP 8.4, Symfony 8.1, Doctrine, Messenger, MySQL, Manticore, Nginx, Docker)
  - [x] quick start (`make docker-up`, migrations, env/ports)
  - [x] project structure (Controller → MessageBus → Handler → EntityFactory/Repository)
  - [x] how to run tests, links to `docs/`
- [x] describe all API endpoints in `README.md` (written once endpoints are final)
  - [x] `GET /api/products`
  - [x] `GET /api/orders`
  - [x] `POST /api/orders`
  - [x] `GET /api/orders/{id}`
  - [x] for each: params, request body example, success/error responses with status codes, curl example
  - [x] Swagger UI URL and `tests/_https/*.http` examples

## 4. Tests (`php bin/phpunit`)

- [x] install `symfony/test-pack`, test database, fixtures
- [x] `WebTestCase`: `POST /api/orders` (success, 422 validation, 400 bad JSON)
  - [x] articles: saved, returned by `GET /api/orders/{id}`, required / non-empty / not an array, per-article validation, all-or-nothing
- [x] `WebTestCase`: `GET /api/orders` (pagination, grouping, search)
- [x] `WebTestCase`: `GET /api/orders/{id}` (200 / 404)
- [x] `WebTestCase`: `GET /api/products` with `MockHttpClient`
- [x] `KernelTestCase`: `CreateOrderHandler` (with articles) chooses the factory; throws `UnprocessableEntityException` when none supports the source
- [x] unit: product price parser
- [x] ready-to-run HTTP requests in `tests/_https/orders` (`post.orders.http`, `post.orders.invalid.http`, `get.orders.collection.http`, `get.orders.search.http`, `get.orders.details.http`); responses checked against the running app

## 5. Database dump

- [x] save the original dump to `docs/dump.original.sql`
- [x] `docs/db-dump-analysis.md`: what is wrong in the dump (keys, types, nullability, naming, indexes, charset, duplicated data) and how to improve it
- [x] Doctrine migration `Version20261123081500` in the project style (up + down tested, schema in sync with the entities)
- [x] `docs/dump.improved.sql`, verified by importing into a clean MySQL

## 6. Production deployment (VPS)

- [x] root `Dockerfile` (stages `php-base`, `vendor`, `app`, `nginx`), `.dockerignore`, `docker-compose.prod.yaml` (no published ports, Traefik labels), `.env.prod.local.dist`
  - [x] `symfony/twig-bundle` moved to `require` (the prod image installs without dev packages and Swagger UI needs it)
  - [x] the search index is created on the first order; a Manticore outage no longer fails `POST /api/orders` (index errors are logged, the order is kept)
- [x] Claude skill `main-dockerfile-fix` (`.claude/skills/main-dockerfile-fix/SKILL.md`, `scripts/check.sh`) and its instructions `docs/main-dockerfile-fix.md`
- [x] `docs/deploy.md`: Cloudflare hostname, deploy key, secrets, first start, verification, updates, rollback, backups, troubleshooting
- [x] `bin/deploy.sh` with `--remote` (ssh to the server), `--ref`, `--no-pull`, `--no-backup`, `--no-prune`, `--reindex`
- [x] `makefile` targets `prod-*`, README "Deployment" section
- [x] checked locally with a throw-away production stack: image builds (both targets), migrations at start, `/api/doc`, POST/GET orders, search with and without Manticore, `bin/deploy.sh --reindex`
- [ ] first deployment on the VPS (done by the owner: `docs/deploy.md`, sections 3-7)

## 7. Final verification

- [x] `docker compose up -d --build`
- [x] `bin/console lint:container`
- [x] `php bin/phpunit` (78 tests)
- [x] manual calls (curl against http://localhost:8080) and Swagger UI (`/api/doc`)
