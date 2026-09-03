<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Pagination',
    required: ['current_page', 'per_page', 'next', 'prev'],
    properties: [
        new OA\Property(property: 'current_page', type: 'integer', example: 1),
        new OA\Property(property: 'per_page', type: 'integer', example: 20),
        new OA\Property(property: 'total', type: 'integer', nullable: true, example: 42),
        new OA\Property(property: 'last_page', type: 'integer', nullable: true, example: 3),
        new OA\Property(
            property: 'next',
            type: 'string',
            nullable: true,
            example: 'http://localhost:5000/api/v1/properties?page=2',
        ),
        new OA\Property(property: 'prev', type: 'string', nullable: true, example: null),
    ],
)]
class PaginationSchema
{
}
