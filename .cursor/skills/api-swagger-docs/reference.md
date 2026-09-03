# API OpenAPI — templates

Existing typed schemas live in one file (multiple `#[OA\Schema]` on `TypedEnumValueSchemas`):

`app/OpenApi/Schemas/EnumValues/TypedEnumValueSchemas.php`

Shared shape only: `app/OpenApi/Schemas/EnumValueSchema.php` (`schema: EnumValue`).

Info + tags: `app/OpenApi/OpenApi.php`. When adding a typed schema, add a row to the Info enum table.

Cases and labels below are **placeholders**. Copy real values from the PHP enumerator and `lang/en` — do not invent CRM absences/orders.

## New typed EnumValue schema

Add another `#[OA\Schema]` on `TypedEnumValueSchemas` (do not create a second EnumValue example):

```php
#[OA\Schema(
    schema: 'OfferStatusEnumValue',
    description: <<<'MD'
Offer status. `value` is the machine code; `label` is the English UI string.

- `pending` — Pending
- `published` — Published
MD,
    required: ['value', 'label'],
    properties: [
        new OA\Property(
            property: 'value',
            description: 'Machine value',
            type: 'string',
            enum: ['pending', 'published'],
            example: 'pending',
        ),
        new OA\Property(property: 'label', description: 'English UI label', type: 'string', example: 'Pending'),
    ],
)]
```

Field on a domain schema:

```php
new OA\Property(property: 'status', ref: '#/components/schemas/OfferStatusEnumValue'),
```

Bare request string (same cases, not an object):

```php
new OA\Property(
    property: 'status',
    description: 'Bare string (not `{value,label}`). `pending` Pending · `published` Published',
    type: 'string',
    enum: ['pending', 'published'],
    example: 'pending',
),
```

## Schema name map (keep in sync with TypedEnumValueSchemas)

Add a row when a real enumerator is documented. Do not reuse another schema’s examples.

| OpenAPI schema | Typical JSON field |
|----------------|-------------------|
| `OfferStatusEnumValue` | offer `status` (example only until the enumerator exists) |

If a field is missing from this table, read the enumerator.

## Operation skeleton

```php
#[OA\Get(
    path: '/offers',
    operationId: 'api.v1.offers.index',
    summary: 'List offers',
    description: 'Paginated offers. `status` — see OfferStatusEnumValue.',
    tags: ['Offers'],
    parameters: [ /* Form Request query only */ ],
    responses: [
        new OA\Response(response: 200, description: 'Offer list', content: new OA\JsonContent(
            allOf: [
                new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
                new OA\Schema(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/OfferListData'),
                ]),
            ],
        )),
        new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
        new OA\Response(response: 429, ref: '#/components/responses/TooManyRequests'),
        new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
    ],
)]
```

Add `NotFound` when the route binds `{model}`. Do not add 401/403.

Attributes: `OpenApi\Attributes as OA`. Tags must match `#[OA\Tag]` in `app/OpenApi/OpenApi.php`.
