<?php

namespace App\OpenApi\Parameters;

use OpenApi\Attributes as OA;

#[OA\Parameter(
    parameter: 'Page',
    name: 'page',
    description: 'Page number (1-based)',
    in: 'query',
    required: false,
    schema: new OA\Schema(type: 'integer', minimum: 1, example: 1),
)]
#[OA\Parameter(
    parameter: 'PerPage',
    name: 'per_page',
    description: 'Items per page (max 100)',
    in: 'query',
    required: false,
    schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, example: 20),
)]
class SharedParameters
{
}
