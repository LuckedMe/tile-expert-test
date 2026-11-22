# Technical specifications — implementation plan

Tick a box (`- [x]`) when a step is done. Original task text (RU): [technical-specifications-ru.md](technical-specifications-ru.md).

## 1. Decisions

- Product endpoint takes query params: `GET /api/products?factory=&collection=&article=` (replaces the `/api/products/{id}` stub).
- Create order is JSON REST only (no SOAP), as hinted in the task ("2? see REST API").
- Ports are configured through environment variables.

## 2. Endpoints

- [ ] `GET /api/products?factory=&collection=&article=`
  - [ ] fetch `https://tile.expert/it/tile/{factory}/{collection}/a/{article}` with Symfony HttpClient
  - [ ] parse the EUR price from the page
  - [ ] respond `{"price": 59.99, "factory": "...", "collection": "...", "article": "..."}`
  - [ ] 404 when the page or price is not found; cache successful lookups (Symfony Cache)
- [ ] `GET /api/orders` — paginated and grouped
  - [ ] params: `page`, `per_page`, `group_by` (day | month | year), `start_day`, `end_day`, `sort`, `query`
  - [ ] response: page, per page, total, pages + `data: [{group, count}]`
- [ ] `POST /api/orders`
  - [ ] `CreateOrderHandler` picks an entity factory by source (HTTP request, `CreateOrderRequest`)
  - [ ] `UnprocessableEntityException` when no factory supports the source
  - [ ] remove the duplicate `OrderCreateRequestEntityFactory`; make `OrderHttpRequestEntityFactory` return a real order
  - [ ] dispatch `OrderCreatedEvent`
- [ ] `GET /api/orders/{id}` — order details with articles; 404 when missing

## 3. Additional

- [ ] Manticore search
  - [ ] add the Manticore service to `docker-compose.yaml`
  - [ ] `orders` index + indexing command
  - [ ] `composer require manticoresoftware/manticoresearch-php`
  - [ ] `query` param of `GET /api/orders` uses the Manticore `*` wildcard search; Manticore returns order ids, MySQL does filtering and grouping
  - [ ] graceful fallback when Manticore is unavailable
- [ ] OpenAPI (Swagger) description of all four endpoints
- [ ] ports in `.env` / `docker-compose.yaml`, `makefile` targets
- [ ] main `README.md` about the project
  - [ ] purpose, stack (PHP 8.4, Symfony 8.1, Doctrine, Messenger, MySQL, Manticore, Nginx, Docker)
  - [ ] quick start (`make docker-up`, migrations, env/ports)
  - [ ] project structure (Controller → MessageBus → Handler → EntityFactory/Repository)
  - [ ] how to run tests, links to `docs/`
- [ ] describe all API endpoints in `README.md` (written once endpoints are final)
  - [ ] `GET /api/products`
  - [ ] `GET /api/orders`
  - [ ] `POST /api/orders`
  - [ ] `GET /api/orders/{id}`
  - [ ] for each: params, request body example, success/error responses with status codes, curl example
  - [ ] Swagger UI URL and `tests/_https/*.http` examples

## 4. Tests (`php bin/phpunit`)

- [ ] install `symfony/test-pack`, test database, fixtures
- [ ] `WebTestCase`: `POST /api/orders` (success, 422 validation, 400 bad JSON)
- [ ] `WebTestCase`: `GET /api/orders` (pagination, grouping, search)
- [ ] `WebTestCase`: `GET /api/orders/{id}` (200 / 404)
- [ ] `WebTestCase`: `GET /api/products` with `MockHttpClient`
- [ ] `KernelTestCase`: `CreateOrderHandler` chooses the factory; throws `UnprocessableEntityException` when none supports the source
- [ ] unit: product price parser

## 5. Database dump

- [ ] save the original dump to `docs/dump.original.sql`
- [ ] `docs/db-dump-analysis.md`: what is wrong in the dump (keys, types, nullability, naming, indexes, charset, duplicated data) and how to improve it
- [ ] Doctrine migrations in the project style (`fix-migrations` skill)
- [ ] `docs/dump.improved.sql`, verified by importing into a clean MySQL

## 6. Final verification

- [ ] `docker compose up -d --build`
- [ ] `bin/console lint:container`
- [ ] `php bin/phpunit`
- [ ] manual calls via `tests/_https/*.http` and Swagger UI
