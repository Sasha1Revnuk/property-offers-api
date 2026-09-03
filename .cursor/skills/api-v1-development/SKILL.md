---
name: api-v1-development
description: >-
  Develops the Property Offers JSON API under /api/v1. Activates for API v1,
  L5-Swagger, OpenAPI, ApiService, unified JSON envelope, Api/V1 controllers,
  or routes/api/v1. Use when adding versioned endpoints or API documentation.
  No authentication.
---

# API v1 Development

Activate `laravel-best-practices` for query, security, and testing depth.

Read the `api-v1` rule (`.cursor/rules/api-v1.mdc`) for conventions.

Detailed checklists: [reference.md](reference.md)

## Architecture

```
/api/v1/*
  → Api/V1 controllers (thin)
  → ApiServiceInterface (JSON envelope)
  → Domain service (OfferService, …)
  → Models
```

There is no separate web/Inertia adapter. Health stays unversioned: `GET /api/health`, `GET /up`.

## Key decisions (locked)

| Topic | Decision |
|-------|----------|
| Auth | None — public API, no Sanctum, no session |
| JSON format | `ApiService` envelope (`success`, `message`, `data`; pagination inside `data`) |
| Docs | L5-Swagger; gated by `API_DOCS_ENABLED` |
| Versioning | `v1` in URL; `v2` only on breaking changes |
| Date-times | ISO 8601 UTC with `Z` via `ApiDateTimeFormatter` |
| Enums | `{ "value", "label" }` via `EnumValueResource`; labels in English |

## Directory map

| Layer | Path |
|-------|------|
| Routes | `routes/api.php`, `routes/api/v1.php` |
| Controllers | `app/Http/Controllers/Api/V1/` |
| Requests | `app/Http/Requests/Api/V1/` |
| Resources | `app/Http/Resources/Api/V1/` |
| API service | `app/Services/Api/` |
| OpenAPI | `app/OpenApi/` |
| Tests | `tests/Feature/Api/V1/` |
| Logs | `storage/logs/api.log` (channel `api`) |

## Controller pattern

```php
public function store(StoreOfferRequest $request): JsonResponse
{
    $offer = $this->offerService->create(CreateOfferDto::fromRequest($request));

    return $this->apiService->created(
        TranslationHelper::get('translations.offer.created'),
        new OfferResource($offer),
    );
}
```

Never call `response()->json()` on `/api/v1` endpoints.

## Enum and dates

Always serialize enums as `{ "value": "...", "label": "..." }` via `EnumValueResource`.

Date-time fields use ISO 8601 UTC with `Z` (e.g. `2026-09-03T17:07:00Z`) via `ApiDateTimeFormatter`.

## Swagger pattern

Activate **`api-swagger-docs`**.

1. Shared envelopes and error responses in `app/OpenApi/Responses/`
2. Domain schemas in `app/OpenApi/Schemas/<Domain>/`
3. `{value,label}` fields: typed `*EnumValue` schemas — **never** `ref` shared `EnumValue` for a domain field
4. Endpoint `#[OA\Get(...)]` with English `summary` + `description` and `ref` to shared components
5. Document every endpoint before merge; `make swagger` after OpenAPI edits

## Environment variables

```env
APP_URL=http://127.0.0.1:5000
L5_SWAGGER_CONST_HOST="${APP_URL}"
L5_SWAGGER_GENERATE_ALWAYS=true          # false on prod
API_DOCS_ENABLED=true                    # false on prod
API_LOG_LEVEL=debug
```

Swagger JSON is generated into `storage/api-docs/` (gitignored). Use `make swagger` after OpenAPI changes; do not commit `api-docs.json`.

## Bootstrap status

Infrastructure (L5-Swagger, `ApiService`, routes) is added incrementally. Check what already exists before creating duplicates.

## New endpoint — quick checklist

See [reference.md](reference.md) for the full checklist.

1. Route → Controller → Form Request → Domain Service → `ApiServiceInterface` response
2. OpenAPI attributes with shared refs
3. Feature test
4. Swagger UI verification (local/dev)
