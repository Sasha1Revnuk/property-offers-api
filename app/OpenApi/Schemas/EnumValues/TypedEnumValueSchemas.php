<?php

namespace App\OpenApi\Schemas\EnumValues;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ImportStatusEnumValue',
    description: <<<'MD'
Import processing status. `value` is the machine code; `label` is the English UI string.

- `pending` — Pending
- `processing` — Processing
- `completed` — Completed
- `failed` — Failed
MD,
    required: ['value', 'label'],
    properties: [
        new OA\Property(
            property: 'value',
            description: 'Machine value',
            type: 'string',
            enum: ['pending', 'processing', 'completed', 'failed'],
            example: 'pending',
        ),
        new OA\Property(property: 'label', description: 'English UI label', type: 'string', example: 'Pending'),
    ],
)]
#[OA\Schema(
    schema: 'ReservationStatusEnumValue',
    description: <<<'MD'
Reservation status. `value` is the machine code; `label` is the English UI string.

- `confirmed` — Confirmed
MD,
    required: ['value', 'label'],
    properties: [
        new OA\Property(
            property: 'value',
            description: 'Machine value',
            type: 'string',
            enum: ['confirmed'],
            example: 'confirmed',
        ),
        new OA\Property(property: 'label', description: 'English UI label', type: 'string', example: 'Confirmed'),
    ],
)]
class TypedEnumValueSchemas
{
}
