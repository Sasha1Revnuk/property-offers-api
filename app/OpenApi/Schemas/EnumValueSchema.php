<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'EnumValue',
    description: 'Shared shape only. Domain fields must ref a typed `*EnumValue` schema.',
    required: ['value', 'label'],
    properties: [
        new OA\Property(property: 'value', description: 'Machine value', type: 'string'),
        new OA\Property(property: 'label', description: 'English UI label', type: 'string'),
    ],
)]
class EnumValueSchema
{
}
