<?php

namespace App\OpenApi\Responses;

use OpenApi\Attributes as OA;

#[OA\Response(
    response: 'NotFound',
    description: 'Resource not found',
    content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope'),
)]
#[OA\Response(
    response: 'ValidationError',
    description: 'Validation failed',
    content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope'),
)]
#[OA\Response(
    response: 'TooManyRequests',
    description: 'Too many requests',
    content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope'),
)]
#[OA\Response(
    response: 'ServerError',
    description: 'Unexpected server error',
    content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope'),
)]
#[OA\Response(
    response: 'Conflict',
    description: 'Conflict (e.g. offer sold out)',
    content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope'),
)]
class SharedResponses
{
}
