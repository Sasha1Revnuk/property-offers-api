---
name: api-swagger-docs
description: >-
  Writes and updates L5-Swagger / OpenAPI for the Property Offers API (/api/v1).
  Use when adding or changing API endpoints, OA attributes, OpenAPI schemas,
  enum documentation, Swagger UI examples, or running make swagger.
  Activates for swagger, OpenAPI, L5-Swagger, #[OA, EnumValue, api-docs, documenting
  /api/v1 routes, or when the user asks to describe allowed enum values.
---

# API Swagger / OpenAPI

Activate together with `api-v1-development`. Read `.cursor/rules/api-v1.mdc`.

**Docs only.** Do not change routes, Form Requests, Resources, or enumerator cases unless the user asked for an API change. OpenAPI must match the **current** JSON contract.

Templates: [reference.md](reference.md)

## Language

| Surface | Language |
|---------|----------|
| `summary`, `description`, tags, parameter/schema prose | **English** |
| Enum `label` examples | **English** (locale `en`) |

Never invent labels. Copy from `lang/en/translations.php` or `Enumerator::getLabel()`.

## Source of truth (read before writing schemas)

1. **Response JSON** — `app/Http/Resources/Api/V1/`
2. **Request/query** — `app/Http/Requests/Api/V1/`
3. **Enum cases** — PHP backed enums under `app/Services/*/Enumerators/`
4. **Labels** — `lang/en/translations.php`

If OpenAPI field names differ from the Resource/Form Request, **fix OpenAPI**, not the API.

## Enums — typed schemas, not shared EnumValue

Shared schema `EnumValue` is **only the shape** `{value, label}`. It must **not** be the type of a domain field. One shared example would make every field look identical in Swagger UI.

For every `{value,label}` field, use a **typed** schema in `app/OpenApi/Schemas/EnumValues/TypedEnumValueSchemas.php`:

- `enum:` on `value` with **all** PHP cases
- description lists every `value` — English `label`
- `example` is a real case for **that** enum

Wire the field with `ref: '#/components/schemas/<Name>EnumValue'`, never `EnumValue`.

Bare strings in query/body (not `{value,label}`) still need `enum:` + English description of each value.

If a new enumerator appears in a Resource, add a typed schema **in the same change**. Keep the name map in [reference.md](reference.md) in sync.

## Operations

On every `#[OA\Get|Post|Put|Patch|Delete]` in `app/Http/Controllers/Api/V1/`:

- `summary` — one English line for the UI list
- `description` — what it does, constraints, which typed enums appear (no auth / “current user”)
- Success response `description` — specific (`Offer list`), not `OK` / `Created`
- `operationId`: `api.v1.<resource>.<action>`
- Shared error `ref`s per response matrix in the `api-v1` rule (no 401/403)
- Query/path params: document only what the Form Request already validates (`enum`, min/max, format)

Do not invent extra query params. Do not add `security` / bearerAuth.

## Dates and envelope

- Date-times: ISO 8601 UTC with `Z` (e.g. `2026-09-03T17:07:00Z`)
- Envelope: `{success, message, data}`; lists `{items, pagination}`; multiple lists `{ <key>: { items, pagination } }`

## After edits

```bash
make swagger
# docker compose exec -T php php artisan l5-swagger:generate --no-interaction
```

Do not commit `storage/api-docs/api-docs.json`.

## Checklist

- [ ] `summary` + `description` on the operation
- [ ] Success description is specific
- [ ] Every `{value,label}` field refs a typed `*EnumValue` schema (not `EnumValue`)
- [ ] Typed schema `enum` + labels match PHP enumerator + `lang/en`
- [ ] Bare request/query strings that are closed sets have `enum:`
- [ ] Examples are real values for **that** field
- [ ] Field names match Resource / Form Request
- [ ] No 401/403 responses
- [ ] `make swagger` succeeds; Swagger UI shows distinct enums per field
