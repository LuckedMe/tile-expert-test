# Plan: docs/technical-specifications.md (checklist plan)

## Context
Tile Expert test task. The original task text (RU) lives in `docs/technical-specifications.md`.
Goal: keep that text under a new name and create a new English `docs/technical-specifications.md`
that is a checkable plan (`- [ ]` / `- [x]`, renders as tickable boxes on GitHub/GitLab/IDEs).
Already done in repo: entities from dump, Messenger, POST/GET order endpoints (WIP), product stub.

## Step 0 – rename (first action on execution)
- `git mv docs/technical-specifications.md docs/technical-specifications-ru.md` (content already read; unchanged).

## Step 1 – write new `docs/technical-specifications.md`
Sections (every item is a checkbox so progress can be ticked):

1. **Requirements summary** – link to `technical-specifications-ru.md`.
2. **Decisions** (defaults, change if wanted)
   - Product endpoint: `GET /api/products?factory=&collection=&article=` (replaces `/api/products/{id}` stub in `src/Controller/Api/Product/GetProductDetailsController.php`).
   - Create order: JSON REST only (no SOAP).
3. **Endpoints**
   - [ ] `GET /api/products` – fetch price (EUR) from `https://tile.expert/it/tile/{factory}/{collection}/a/{article}` using Symfony HttpClient; parse price; JSON `{price,factory,collection,article}`; 404 when page/price missing; cache via Symfony Cache.
   - [ ] `GET /api/orders` – paginated collection, grouped by day/month/year (`group_by` param); response: page, per_page, total, pages + `data:[{group,count}]`. Reuse `GetOrderCollectionController`, `OrderCriteriaDTO(Factory)`, `OrderRepository` + `PaginatorRepositoryTrait`.
   - [ ] `POST /api/orders` – finish `CreateOrderHandler` + `OrderEntityFactoryInterface` factories (fix tag via `_instanceof` – done; remove duplicate `OrderCreateRequestEntityFactory`; make `OrderHttpRequestEntityFactory` return a real order; `UnprocessableEntityException` if no factory).
   - [ ] `GET /api/orders/{id}` – details with articles (`GetOrderDetailsController`), 404 via `EntityNotFoundException`.
4. **Additional**
   - [ ] Manticore: add `manticoresearch/manticore` service to `docker-compose.yaml`; index `orders` (table via migration/SQL script + indexer command); `composer require manticoresoftware/manticoresearch-php`; `OrderSearchService`; in `GET /api/orders` the `query` param runs a Manticore `*` wildcard search (`query=*` / `foo*`) returning ids, then MySQL filters/groups by those ids; fallback when Manticore is down.
   - [ ] Swagger/OpenAPI docs for all four endpoints (Nelmio already in use; `#[OA\...]` attributes).
   - [ ] Ports in env vars (`.env`, `docker-compose.yaml`), `makefile` targets.
   - [ ] **README.md** (currently just `# tile-expert-test`): add/update main `README.md` about the project — purpose, stack (PHP 8.4, Symfony 8.1, Doctrine, Messenger, MySQL, Manticore, Nginx, Docker), quick start (`make docker-up`, migrations, env/ports), project structure (Controller → MessageBus → Handler → EntityFactory/Repository), running tests, link to `docs/`.
   - [ ] **README.md – API endpoints**: describe every endpoint (method, path, params, request body example, success/error responses with status codes, curl example): `GET /api/products`, `GET /api/orders`, `POST /api/orders`, `GET /api/orders/{id}`; mention Swagger UI URL (Nelmio) and `tests/_https/*.http` examples. Written after the endpoints are final so docs match behaviour.
5. **Tests** (`symfony/test-pack`, `php bin/phpunit`, test DB + fixtures)
   - [ ] `WebTestCase`: POST /api/orders (201/204, 422 validation, 400 bad JSON)
   - [ ] `WebTestCase`: GET /api/orders (pagination, grouping, search), GET /api/orders/{id} (200/404)
   - [ ] `WebTestCase`: GET /api/products with mocked HttpClient (`MockHttpClient`)
   - [ ] `KernelTestCase`: `CreateOrderHandler` – picks factory by source, throws `UnprocessableEntityException` when none supports
   - [ ] Unit: product price parser, `MessageBusHandler`
6. **DB dump analysis** (`docs/db-dump-analysis.md`)
   - [ ] Fetch original `dump.sql` (gitlab link in RU spec) into `docs/dump.original.sql`
   - [ ] List problems: missing/wrong PKs and FKs, types (money as float, dates as strings/int), nullable flags, bad naming, missing indexes on filter/group columns (`create_date`, `hash`, `token`), charset/collation, denormalised/duplicated columns, ENUM-as-int flags etc. (confirmed against the real dump before writing)
   - [ ] Write fixes + rationale
7. **Improved dump**
   - [ ] `docs/dump.improved.sql` generated from migrations (`migrations/2026/*`), verified by importing into clean MySQL
   - [ ] Doctrine migrations written in the project style (use `fix-migrations` skill)
8. **Verification**
   - `docker compose up -d --build`, `bin/console lint:container`, `php bin/phpunit`
   - manual calls via `tests/_https/*.http`, Swagger UI, Manticore `SHOW TABLES` / `SELECT ... MATCH('*')`

## Critical files
`docs/technical-specifications.md` (+ `-ru`), `src/Controller/Api/**`, `src/MessageHandler/Command/Order/CreateOrderHandler.php`,
`src/EntityFactory/*`, `src/Repository/OrderRepository.php`, `config/services.yaml`, `docker-compose.yaml`, `tests/`.
