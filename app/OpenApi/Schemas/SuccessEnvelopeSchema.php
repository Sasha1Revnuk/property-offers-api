<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SuccessEnvelope',
    required: ['success', 'message', 'data'],
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string', example: 'Properties fetched.'),
        new OA\Property(property: 'data', description: 'Payload; shape depends on the endpoint'),
    ],
)]
class SuccessEnvelopeSchema
{
}
