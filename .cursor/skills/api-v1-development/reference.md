# API v1 — Reference

## API versioning

### Stay on v1 when

- Adding new endpoints
- Adding optional response fields
- Adding optional query parameters with defaults
- Internal service refactoring without JSON contract change

### Create v2 when

- Removing or renaming response fields
- Changing field types
- Changing endpoint semantics
- Old clients still depend on the previous contract

### v2 migration pattern

```
routes/api/
├── v1.php    # frozen, bugfix only
└── v2.php    # active development

app/Http/Controllers/Api/
├── V1/       # deprecated
└── V2/       # current
```

Shared: domain services, models. Separate: controllers, resources, OpenAPI schemas, Form Requests.

L5-Swagger: two documentation entries (`api-v1`, `api-v2`) in `config/l5-swagger.php` when v2 exists.

---

## Swagger environment setup

### config/l5-swagger.php (key settings)

```php
'defaults' => [
    'routes' => [
        'api' => 'api/documentation',
        'middleware' => [
            'api' => ['api.docs'],
        ],
    ],
    'paths' => [
        'annotations' => [
            base_path('app/OpenApi'),
            base_path('app/Http/Controllers/Api/V1'),
        ],
    ],
    'generate_always' => env('L5_SWAGGER_GENERATE_ALWAYS', false),
    'constants' => [
        'L5_SWAGGER_CONST_HOST' => env('L5_SWAGGER_CONST_HOST', env('APP_URL')),
    ],
],
```

### Gate middleware `api.docs`

```php
if (! config('api.docs_enabled')) {
    abort(404);
}
```

Config key from `API_DOCS_ENABLED`.

### Per environment

| | local | staging | prod |
|---|-------|---------|------|
| `API_DOCS_ENABLED` | true | as needed | false |
| `L5_SWAGGER_GENERATE_ALWAYS` | true | true or generate on deploy | false |
| `L5_SWAGGER_CONST_HOST` | `APP_URL` (nginx port **5000**, not `:8000`) | staging URL | production URL |
| Commit `storage/api-docs/` | no | no | no |

```bash
make swagger
# docker compose exec -T php php artisan l5-swagger:generate --no-interaction
```

---

## OpenAPI reusable components

### Envelopes

```php
// app/OpenApi/Schemas/PaginationSchema.php
#[OA\Schema(
    schema: 'Pagination',
    properties: [
        new OA\Property(property: 'current_page', type: 'integer', example: 1),
        new OA\Property(property: 'per_page', type: 'integer', example: 20),
        new OA\Property(property: 'total', type: 'integer', example: 150),
        new OA\Property(property: 'last_page', type: 'integer', example: 8),
    ]
)]
```

Paginated list `data`: `{ items: T[], pagination: Pagination }`.

Multiple paginated lists: `{ <key>: { items, pagination }, ... }`.

```php
// app/OpenApi/Schemas/SuccessEnvelope.php
#[OA\Schema(
    schema: 'SuccessEnvelope',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string'),
        new OA\Property(property: 'data'),
    ]
)]
```

```php
// app/OpenApi/Schemas/ErrorEnvelope.php
#[OA\Schema(
    schema: 'ErrorEnvelope',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(property: 'message', type: 'string'),
        new OA\Property(property: 'errors', type: 'object', additionalProperties: new OA\AdditionalProperties(
            type: 'array',
            items: new OA\Items(type: 'string'),
        )),
    ]
)]
```

### Shared responses

There is no auth — do **not** add Unauthorized / Forbidden as required components.

```php
new OA\Response(ref: '#/components/responses/NotFound'),
new OA\Response(ref: '#/components/responses/ValidationError'),
new OA\Response(ref: '#/components/responses/TooManyRequests'),
new OA\Response(ref: '#/components/responses/ServerError'),
```

---

## ApiService interface

```php
interface ApiServiceInterface
{
    public function success(mixed $data, string $message, int $status = 200): JsonResponse;

    public function created(mixed $data, string $message): JsonResponse;

    public function validationError(array $errors, string $message): JsonResponse;

    public function notFound(string $message): JsonResponse;

    public function serverError(string $internalMessage, string $clientMessage): JsonResponse;
}
```

Bind in a service provider:

```php
$this->app->bind(ApiServiceInterface::class, ApiService::class);
```

---

## Logging channel

```php
// config/logging.php
'api' => [
    'driver' => 'daily',
    'path' => storage_path('logs/api.log'),
    'level' => env('API_LOG_LEVEL', 'debug'),
    'days' => 30,
],
```

### Log entry structure

```json
{
  "request_id": "uuid",
  "method": "GET",
  "path": "/api/v1/offers/5",
  "ip": "192.168.1.1",
  "user_agent": "...",
  "status_code": 200,
  "duration_ms": 45
}
```

For 5xx add `error`, `exception_class`, `trace`. Generic message to the client.

---

## New endpoint — full checklist

### Backend

- [ ] Route in `routes/api/v1.php` with name prefix `api.v1.`
- [ ] Controller method in `Api/V1/<Domain>/`
- [ ] Form Request: rules; `authorize()` returns `true`
- [ ] Domain service call (existing interface, new method if needed)
- [ ] Unit test for new service method (if the service changed)
- [ ] Response via `ApiServiceInterface`
- [ ] JsonResource for `data` when it is a structured object
- [ ] Enum fields use `EnumValueResource` (`value` + `label`, English)
- [ ] Date-times via `ApiDateTimeFormatter` (ISO 8601 `Z`)

### OpenAPI

Follow skill **`api-swagger-docs`**.

- [ ] `#[OA\Get|Post|Put|Patch|Delete(...)]` on the controller method
- [ ] English `summary` + `description`; success description is not `OK`/`Created`
- [ ] `operationId`: `api.v1.<resource>.<action>`
- [ ] `tags`: domain name (e.g. `Offers`)
- [ ] No `security` / bearerAuth
- [ ] Request body schema (if POST/PUT/PATCH)
- [ ] Response 200/201 refs domain schema wrapped in `SuccessEnvelope`
- [ ] `{value,label}` fields ref typed `*EnumValue` (not shared `EnumValue`); all cases + English labels
- [ ] Closed request/query sets have `enum:`
- [ ] Shared error refs: ValidationError, TooManyRequests, ServerError, + NotFound when `{model}` binding exists

### Tests

- [ ] Feature test: happy path
- [ ] Feature test: 422 validation errors
- [ ] Feature test: 404 when the route has a model and the id is missing
- [ ] Assert JSON envelope (`success`, `message`, `data`)
- [ ] Paginated endpoints: `data.items` + `data.pagination` (not top-level `meta`)
- [ ] Do not add 401/403 tests

### Verify

- [ ] `php artisan route:list --path=api/v1` (inside the `php` container)
- [ ] Swagger UI shows the endpoint (when docs enabled)
- [ ] `make swagger`
- [ ] Entry in `storage/logs/api.log` when logging middleware exists

---

## Bootstrap order (when implementation starts)

1. `composer require darkaonline/l5-swagger`
2. Publish config
3. Config for docs gate + log level (`API_DOCS_ENABLED`, `API_LOG_LEVEL`)
4. `routes/api.php` loads `routes/api/v1.php`
5. `ApiService` + V1 Resources (`SuccessResource`, `ErrorResource`, `EnumValueResource`, pagination helpers)
6. `ApiDateTimeFormatter`
7. `app/OpenApi/` base components
8. Docs-gate middleware + request logger
9. Logging channel `api`
