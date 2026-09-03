# Property Offers API

JSON API for property offers built with **Laravel 13** and **PHP 8.5**. No SPA, Blade UI, session, or authentication.

Repository: [https://github.com/Sasha1Revnuk/property-offers-api](https://github.com/Sasha1Revnuk/property-offers-api)

| Layer | Stack |
| --- | --- |
| API | Laravel 13, PHP 8.5 |
| Database | MySQL 8.0.23 |
| Cache / queues | Redis 7 |
| Docs | L5-Swagger (OpenAPI) |
| Tests | PHPUnit |

---

## Requirements

**Shared**

- PHP 8.5 (Composer requires `^8.3`) with extensions used by Laravel (including `pdo_mysql`, `redis` / phpredis, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`)
- Composer 2
- MySQL 8.0.23+
- Redis 7

**Docker path:** Docker and Docker Compose.

**Host path:** the stack above installed and running locally (or equivalent).

---

## Run with Docker (recommended)

Defaults in `.env.example` use Compose service hostnames (`DB_HOST=db`, `REDIS_HOST=redis`). HTTP is exposed on port **5000**.

### Quick start

```bash
make setup
```

`make setup` may prompt for sudo. The PHP container user (`fpm_user`) password is **`123`**.

This will:

1. Copy `.env.example` → `.env` if `.env` is missing
2. Build and start Compose services (`php`, `nginx`, `db`, `redis`, `queue`)
3. Wait for MySQL, then run `composer install`, generate `APP_KEY` if empty, link storage
4. Run `php artisan migrate --seed` (seeds suppliers `supplier-a` and `supplier-b`)
5. Generate OpenAPI docs

Then open:

- API base: [http://127.0.0.1:5000](http://127.0.0.1:5000)
- Swagger UI: [http://127.0.0.1:5000/api/documentation](http://127.0.0.1:5000/api/documentation)

The `queue` service already runs `php artisan queue:work redis`.

### Other Makefile targets

| Command | Purpose |
| --- | --- |
| `make setup` | One-shot bootstrap (preferred) |
| `make init` | Build, up, composer, key, storage (no migrate/seed) |
| `make up` / `make down` | Start / stop containers |
| `make swagger` | Regenerate OpenAPI |
| `make stan` | PHPStan |
| `make php` | Shell in the `php` container |

### Commands inside Docker

```bash
docker compose exec -T php php artisan migrate --seed --no-interaction
docker compose exec -T php php artisan queue:work redis
docker compose exec -T php composer test
docker compose exec -T php composer lint
docker compose exec -T php composer lint:check
docker compose exec -T php composer stan
# or: composer types:check
docker compose exec -T php php artisan l5-swagger:generate --no-interaction
```

---

## Run without Docker (host)

1. Install PHP 8.5, Composer, MySQL 8, and Redis 7 locally.

2. Create env and point hosts at localhost:

```bash
cp .env.example .env
```

Edit `.env`:

```env
APP_URL=http://127.0.0.1:8000
L5_SWAGGER_CONST_HOST="${APP_URL}"

DB_HOST=127.0.0.1
DB_PORT=3306
# DB_DATABASE / DB_USERNAME / DB_PASSWORD — match your local MySQL

REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

3. Install and bootstrap:

```bash
composer install
php artisan key:generate
php artisan migrate --seed
php artisan l5-swagger:generate --no-interaction
```

4. Run the HTTP server and a queue worker (separate terminals):

```bash
php artisan serve
php artisan queue:work redis
```

Or point local nginx / PHP-FPM at `public/`. Use `APP_URL` that matches your vhost.

Host equivalents of quality commands:

```bash
composer test
composer lint
composer lint:check
composer stan
# or: composer types:check
php artisan test --compact
```

---

## API endpoints

Versioned product routes under `/api/v1` (no auth):

| Method | Path | Status | Description |
| --- | --- | --- | --- |
| `POST` | `/api/v1/imports` | 202 | Accept an async import |
| `GET` | `/api/v1/imports/{import}` | 200 | Import status |
| `GET` | `/api/v1/properties` | 200 | Search properties (cheapest valid offer) |
| `POST` | `/api/v1/offers/{offer}/reservations` | 201 | Reserve an offer |

Query params for properties: `city` (optional), `check_in`, `check_out`, `guests`, `page`, `per_page` (optional).

Operational (unversioned):

| Path | Role |
| --- | --- |
| `GET /` | JSON API info |
| `GET /api/health` | App health |
| `GET /up` | Laravel health |

---

## Response contract

All `/api/v1` responses use a unified envelope:

```json
{
  "success": true,
  "message": "...",
  "data": {}
}
```

Errors add `errors` (and set `success` to `false`). There is no top-level `meta`.

**Enums** (import/reservation status, etc.) are objects, not bare strings:

```json
{ "value": "pending", "label": "Pending" }
```

**Paginated lists** nest pagination inside `data`. Property search uses `simplePaginate()`, so `total` and `last_page` are `null`; `next` / `prev` / `per_page` are always present:

```json
{
  "success": true,
  "message": "...",
  "data": {
    "items": [],
    "pagination": {
      "current_page": 1,
      "per_page": 20,
      "total": null,
      "last_page": null,
      "next": "http://127.0.0.1:5000/api/v1/properties?page=2",
      "prev": null
    }
  }
}
```

Date-times in the API are ISO 8601 UTC with `Z` (e.g. `2026-09-03T17:07:00Z`).

---

## Import idempotency

Imports are keyed by **UNIQUE (`supplier_id`, `external_import_id`)**.

- `POST /api/v1/imports` uses `firstOrCreate` on that key.
- A `ProcessImportJob` is dispatched **only** when the import row was newly created (`wasRecentlyCreated`).
- Repeating the same request returns the same import and does **not** re-dispatch the job.
- Raw offers are stored on `imports.payload`; the queue receives only `import_id` (not tens of thousands of offers through Redis).
- Offers upsert by **UNIQUE (`supplier_id`, `external_id`)** so re-imports update existing rows.

---

## Reservation safety (double-booking)

`ReservationService` runs inside a DB transaction and locks the offer row with **`SELECT ... FOR UPDATE`** (`lockForUpdate()`).

- Concurrent requests for the same offer wait on the row lock.
- After the first commit, a second request sees the updated `available_units` and gets **409 Conflict** when sold out (no negative stock).
- Repeating the same `client_reference` returns the existing reservation (idempotent booking).

---

## Scale notes

- **Import:** bulk `upsert` for properties and offers in chunks of ~1000, with short per-chunk transactions; `processed_offers` increments per chunk.
- **Search:** cheapest valid offer is chosen in MySQL via a window function (`ROW_NUMBER` over `property_id` ordered by `price`); composite index `(check_in, check_out, property_id, price)` supports the filter and order.
- **Reservations:** `lockForUpdate` contends only on the locked offer row, so other offers stay available for concurrent booking.

---

## Environment files

[`.env.example`](.env.example) is the Docker-oriented template (`DB_HOST=db`, `REDIS_HOST=redis`, `QUEUE_CONNECTION=redis`, `CACHE_STORE=redis`, `API_DOCS_ENABLED`, `L5_SWAGGER_*`). No real secrets; leave `APP_KEY` empty until generate.

For host runs, override `DB_*` / `REDIS_*` / `APP_URL` as described above.
