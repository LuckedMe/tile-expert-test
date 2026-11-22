# Database dump analysis

The task provides [dump.original.sql](dump.original.sql) (two tables: `orders` and `orders_article`) and asks
what is wrong with it and how to improve it. The improved schema is [dump.improved.sql](dump.improved.sql); it is
generated from the Doctrine migrations in `migrations/2026/` and was verified by importing it into an empty MySQL 8 database
(and `doctrine:schema:validate` reports that the entities match the migrated schema).

Legend: **fixed** = done in the improved dump / migrations, **recommended** = needs a product decision or changes outside
this task (new tables, other services), so it is described but not applied.

## 1. Summary

The dump is a faithful copy of a legacy table: it works, but it does not protect the data (no foreign key, no uniqueness,
no ranges), stores money as floating point numbers, encodes business states as magic numbers, and keeps ~80 unrelated columns
in one table. The most dangerous points are the first three of section 2.

## 2. What is wrong and how it was improved

### Integrity

| # | Problem | Why it matters | Improvement | Status |
|---|---|---|---|---|
| 1 | `orders_article.orders_id` has **no foreign key** and is nullable | an article can point to a deleted/non-existent order or to none; deleting an order leaves garbage | `FOREIGN KEY (orders_id) REFERENCES orders(id) ON DELETE CASCADE`, column `NOT NULL` | fixed |
| 2 | `orders.hash` is only a **plain index** (`IDX_5`) although it identifies an order | duplicates are possible (a check in the application is racy under concurrency) | `UNIQUE KEY uniq_orders_hash (hash)` | fixed |
| 3 | Money, rates, quantities and weights are `DOUBLE` | binary floating point cannot store `0.1`/`59.99` exactly; sums drift, equality comparisons fail | `DECIMAL`: prices/delivery `(12,2)`/`(10,2)`, rate `(12,6)`, quantities and weights `(x,3)` | fixed |
| 4 | No range checks | negative prices/quantities, discount of 250 % | `CHECK` constraints: `discount BETWEEN 0 AND 100` (and `UNSIGNED`), `amount > 0`, `price >= 0` | fixed |
| 5 | `user_id`, `delivery_country`, `address_payer`, `article_id` are bare integers without a referenced table | no referential integrity, nothing says what they point to (`article_id` is commented "Collection ID") | add FKs when the `users`, `countries`, `addresses` and catalogue tables are in the same database; rename `article_id` to what it references | recommended |
| 6 | `number` (order number) can be `NULL` and is not unique | two orders can share a number | `UNIQUE (number)` once numbering rules are known | recommended |
| 7 | No uniqueness of an article inside an order | the same article can be added twice | `UNIQUE (orders_id, article_id)` if the business rule says so (this index would also replace `fk_orders_id`) | recommended |

### Data types

| # | Problem | Improvement | Status |
|---|---|---|---|
| 8 | `status INT`, `vat_type INT`, `pay_type SMALLINT` – magic numbers with no dictionary | `ENUM` (`created, canceled, confirmed, paid, pickup_ready`; `vat-type-1..3`; `visa, mastercard, paypal`), mapped to PHP backed enums | fixed |
| 9 | `currency VARCHAR(3)`, `measure VARCHAR(3)` – free text; `orders_article.measure` is `VARCHAR(2)` and `currency` is nullable | `ENUM('USD','EUR')`, `ENUM('m','mm','sm','kg')` on `orders`; same types and `NOT NULL` on `orders_article` | `orders` fixed, `orders_article` recommended |
| 10 | `step SMALLINT` is used as a boolean; other flags are nullable `TINYINT(1)` (`accept_pay`, `product_review`, `process`, ...) – three states (`NULL`/0/1) where two are meant | `step TINYINT(1) NOT NULL`; make the flags `NOT NULL DEFAULT 0` | `step` fixed, flags recommended |
| 11 | Too short columns: `manager_name VARCHAR(20)`, `manager_email VARCHAR(30)`, `manager_phone VARCHAR(20)` (an e-mail may have 254 characters) | `100`, `255`, `50` | fixed |
| 12 | `warehouse_data`, `bank_details` are `LONGTEXT` with unstructured content | `JSON` for `warehouse_data`; bank details in their own, encrypted table | recommended |
| 13 | Date columns are inconsistent: `delivery_time_*` are `DATE`, `proposed_date`/`ship_date`/`sending_date` are `DATETIME`; `sending_date` ("calculated delivery date") and `proposed_date` ("estimated delivery date") mean the same | one type per meaning; keep one of the two columns | recommended |
| 14 | `create_date`/`update_date` have no defaults and no time zone convention | store UTC; `DEFAULT CURRENT_TIMESTAMP` / `ON UPDATE CURRENT_TIMESTAMP` (or keep it in the ORM, as the project does) | recommended |

### Design

| # | Problem | Improvement | Status |
|---|---|---|---|
| 15 | `orders` has ~80 columns that mix the order, the client, the delivery address, the carrier, the manager, payment, delivery-date calculation and review flags (`avg_row_length = 2209` in the dump) | split into `orders`, `order_delivery` (address, phone), `order_client`, `order_delivery_dates` ...; carriers and managers become reference tables (`carrier_id`, `manager_id`) instead of name/e-mail/phone copied into every order | recommended |
| 16 | Four pairs of delivery dates (`delivery_time_*`, `*_confirm_*`, `*_fast_pay_*`, `*_old_*`); the comments of `delivery_time_min/max` and `delivery_time_fast_pay_min/max` are identical, so the difference is unknown | one table of "delivery estimates" with a `type` column (`estimated, confirmed, fast_pay, previous`) | recommended |
| 17 | Money is duplicated: `delivery` and `delivery_price_euro`, `price` and `price_eur`, plus `cur_rate` and `payment_euro` | store the amount in the order currency plus the rate; derive the euro value | recommended |
| 18 | Naming: `orders` (plural) vs `orders_article` (singular article, plural id), `sex`, `process`, `mirror`, `pay_type` | `orders` / `order_articles` (or `order_items`) with `order_id`; `gender`, `payment_type`; descriptive names | recommended |
| 19 | Comments exist only in Russian and contain typos ("клинта", "треккинга"); some say "if true" for a `SMALLINT` | comments translated to English and corrected | fixed |

### Indexes and storage

| # | Problem | Improvement | Status |
|---|---|---|---|
| 20 | Index names `IDX_1..IDX_5`, `IDX_318C0B7C...` say nothing | named by purpose: `idx_user_id`, `idx_create_date_status`, `idx_article_id`, ... | fixed |
| 21 | `IDX_3 (create_date)` is a prefix of `IDX_4 (create_date, status)` – redundant, it only slows writes | removed; `(create_date, status)` serves the "orders per day/month/year" query | fixed |
| 22 | `token` (unique user hash) and `email` are used to find a customer's orders but are not indexed | add `INDEX (token)` / `INDEX (email)` once such queries exist (not added: no endpoint uses them yet) | recommended |
| 23 | No `ENGINE`, `CHARSET`, `COLLATE`; a stray `avg_row_length` table option | `ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci`, no `avg_row_length` | fixed |

### Security / privacy

| # | Problem | Improvement | Status |
|---|---|---|---|
| 24 | Personal data (name, e-mail, phone, address) and **bank details** are stored in clear text in the same table as the order | encrypt bank details (or keep them in a payment service), define retention/anonymisation rules (GDPR) | recommended |
| 25 | `token` (64 chars) is a bearer-like secret stored as plain text | store a hash of the token | recommended |

## 3. What the migration changes

`migrations/2026/Version20261121231907.php` and `Version20261122061641.php` (done earlier) translated the dump into
Doctrine-managed tables (English comments, `utf8mb4`, named indexes, FK with `ON DELETE CASCADE`) and replaced the magic numbers
and free text by `ENUM`s. `Version20261123081500.php` applies the rest of the "fixed" items:

* `orders`: `idx_hash` -> `UNIQUE uniq_orders_hash`; `idx_create_date` dropped; `discount SMALLINT UNSIGNED` + `CHECK`;
  `delivery`, `delivery_price_euro`, `cur_rate`, `weight_gross` -> `DECIMAL`; wider `manager_*` columns;
* `orders_article`: `orders_id NOT NULL` (the foreign key is recreated, MySQL does not allow changing a column used by a key);
  `amount`, `price`, `price_eur`, `weight`, `packaging_count`, `pallet`, `packaging` -> `DECIMAL`; `CHECK` constraints.

The migration has a working `down()`. The entities (`src/Entity/Order*.php`) were updated to match: decimal columns are
mapped as strings by Doctrine and exposed as `float` by the getters, the setters accept `float|string`.

## 4. Evidence from this project

* The development database had **16 identical orders** (same `hash`, created by repeated `POST /api/orders` with the sample
  request): the old non-unique `hash` index allowed it. Applying the migration fails on such data, deliberately – duplicates
  must be resolved by a human first (see below).
* The application checks the hash with the `EntityNotExists` validator, but a check followed by an insert is not atomic;
  only the unique key closes the race.

## 5. Applying the migration to an existing database

Check the data first – the migration fails instead of silently changing or deleting rows:

```sql
-- duplicated hashes (the unique key would fail)
SELECT hash, COUNT(*) AS cnt, GROUP_CONCAT(id) AS ids FROM orders GROUP BY hash HAVING cnt > 1;

-- articles without an order (orders_id NOT NULL + foreign key would fail)
SELECT a.id FROM orders_article a LEFT JOIN orders o ON o.id = a.orders_id WHERE o.id IS NULL;

-- values that DECIMAL would round / constraints that would fail
SELECT id FROM orders_article WHERE price <> ROUND(price, 2) OR price_eur <> ROUND(price_eur, 2);
SELECT id FROM orders_article WHERE amount <= 0 OR price < 0;
SELECT id FROM orders WHERE discount NOT BETWEEN 0 AND 100;
```

Then run `bin/console doctrine:migrations:migrate`. To start from the improved schema without Doctrine, import
[dump.improved.sql](dump.improved.sql) into an empty database.
