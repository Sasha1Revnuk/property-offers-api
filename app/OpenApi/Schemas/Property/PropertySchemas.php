<?php

namespace App\OpenApi\Schemas\Property;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'BestOffer',
    required: ['id', 'supplier', 'price', 'currency', 'available_units', 'expires_at'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 125),
        new OA\Property(property: 'supplier', type: 'string', example: 'supplier-a'),
        new OA\Property(
            property: 'price',
            description: 'Minor currency units (integer)',
            type: 'integer',
            example: 72500,
        ),
        new OA\Property(property: 'currency', type: 'string', example: 'EUR'),
        new OA\Property(property: 'available_units', type: 'integer', example: 2),
        new OA\Property(
            property: 'expires_at',
            type: 'string',
            format: 'date-time',
            nullable: true,
            example: '2026-09-10T23:59:59Z',
        ),
    ],
)]
#[OA\Schema(
    schema: 'Property',
    required: ['code', 'name', 'city', 'best_offer'],
    properties: [
        new OA\Property(property: 'code', type: 'string', example: 'BCN-0001'),
        new OA\Property(property: 'name', type: 'string', example: 'Gothic Quarter Apartment'),
        new OA\Property(property: 'city', type: 'string', example: 'Barcelona'),
        new OA\Property(property: 'best_offer', ref: '#/components/schemas/BestOffer'),
    ],
)]
#[OA\Schema(
    schema: 'PropertyListData',
    required: ['items', 'pagination'],
    properties: [
        new OA\Property(
            property: 'items',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/Property'),
        ),
        new OA\Property(property: 'pagination', ref: '#/components/schemas/Pagination'),
    ],
)]
class PropertySchemas
{
}
