<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ErrorEnvelope',
    required: ['success', 'message'],
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
        new OA\Property(
            property: 'errors',
            description: 'Field validation errors (present on 422)',
            type: 'object',
            additionalProperties: new OA\AdditionalProperties(
                type: 'array',
                items: new OA\Items(type: 'string'),
            ),
            example: ['check_in' => ['The check in field is required.']],
        ),
    ],
)]
class ErrorEnvelopeSchema
{
}
