<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

if (! defined('L5_SWAGGER_CONST_HOST')) {
    define('L5_SWAGGER_CONST_HOST', 'http://localhost:5000');
}

#[OA\Info(
    version: '1.0.0',
    title: 'Property Offers API',
    description: <<<'MD'
JSON API for property offers: async supplier imports, cheapest-offer search, and reservations.

## Envelope

All `/api/v1` responses use `{ success, message, data }` (plus `errors` on failure). Paginated lists put `{ items, pagination }` inside `data`.

## Status enums

Domain status fields are `{ value, label }` objects. Use the typed schemas below (not the shared `EnumValue` shape alone):

| Schema | Values |
|--------|--------|
| `ImportStatusEnumValue` | `pending`, `processing`, `completed`, `failed` |
| `ReservationStatusEnumValue` | `confirmed` |

## Auth

There is no authentication.
MD,
)]
#[OA\Server(
    url: L5_SWAGGER_CONST_HOST,
    description: 'Current environment',
)]
#[OA\Tag(name: 'Imports', description: 'Async supplier offer imports and status')]
#[OA\Tag(name: 'Properties', description: 'Search properties by cheapest matching offer')]
#[OA\Tag(name: 'Reservations', description: 'Reserve an offer unit')]
class OpenApi
{
}
