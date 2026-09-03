<?php

namespace App\OpenApi\Schemas\Import;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'StoreImportRequest',
    required: ['supplier', 'external_import_id', 'sent_at', 'offers'],
    properties: [
        new OA\Property(property: 'supplier', type: 'string', example: 'supplier-a'),
        new OA\Property(property: 'external_import_id', type: 'string', example: 'imp-2026-09-01-001'),
        new OA\Property(
            property: 'sent_at',
            description: 'ISO 8601 UTC with Z',
            type: 'string',
            format: 'date-time',
            example: '2026-09-01T10:00:00Z',
        ),
        new OA\Property(
            property: 'offers',
            type: 'array',
            minItems: 1,
            items: new OA\Items(ref: '#/components/schemas/StoreImportOffer'),
        ),
    ],
)]
#[OA\Schema(
    schema: 'StoreImportOffer',
    required: [
        'external_id',
        'property',
        'check_in',
        'check_out',
        'max_guests',
        'price',
        'currency',
        'available_units',
        'expires_at',
    ],
    properties: [
        new OA\Property(property: 'external_id', type: 'string', example: 'a-1001'),
        new OA\Property(property: 'property', ref: '#/components/schemas/StoreImportProperty'),
        new OA\Property(property: 'check_in', type: 'string', format: 'date', example: '2026-09-10'),
        new OA\Property(property: 'check_out', type: 'string', format: 'date', example: '2026-09-15'),
        new OA\Property(property: 'max_guests', type: 'integer', minimum: 1, example: 2),
        new OA\Property(
            property: 'price',
            description: 'Minor currency units (integer)',
            type: 'integer',
            minimum: 0,
            example: 72500,
        ),
        new OA\Property(property: 'currency', type: 'string', minLength: 3, maxLength: 3, example: 'EUR'),
        new OA\Property(property: 'available_units', type: 'integer', minimum: 0, example: 2),
        new OA\Property(
            property: 'expires_at',
            description: 'ISO 8601 UTC with Z',
            type: 'string',
            format: 'date-time',
            example: '2026-09-10T23:59:59Z',
        ),
    ],
)]
#[OA\Schema(
    schema: 'StoreImportProperty',
    required: ['code', 'name', 'city'],
    properties: [
        new OA\Property(property: 'code', type: 'string', example: 'BCN-0001'),
        new OA\Property(property: 'name', type: 'string', example: 'Gothic Quarter Apartment'),
        new OA\Property(property: 'city', type: 'string', example: 'Barcelona'),
    ],
)]
#[OA\Schema(
    schema: 'ImportAccepted',
    required: ['id', 'status'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 15),
        new OA\Property(property: 'status', ref: '#/components/schemas/ImportStatusEnumValue'),
    ],
)]
#[OA\Schema(
    schema: 'Import',
    required: [
        'id',
        'supplier',
        'external_import_id',
        'sent_at',
        'status',
        'total_offers',
        'processed_offers',
        'error',
        'created_at',
        'completed_at',
    ],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 15),
        new OA\Property(property: 'supplier', type: 'string', example: 'supplier-a'),
        new OA\Property(property: 'external_import_id', type: 'string', example: 'imp-2026-09-01-001'),
        new OA\Property(
            property: 'sent_at',
            type: 'string',
            format: 'date-time',
            nullable: true,
            example: '2026-09-01T10:00:00Z',
        ),
        new OA\Property(property: 'status', ref: '#/components/schemas/ImportStatusEnumValue'),
        new OA\Property(property: 'total_offers', type: 'integer', example: 120),
        new OA\Property(property: 'processed_offers', type: 'integer', example: 120),
        new OA\Property(property: 'error', type: 'string', nullable: true, example: null),
        new OA\Property(
            property: 'created_at',
            type: 'string',
            format: 'date-time',
            nullable: true,
            example: '2026-09-01T10:00:01Z',
        ),
        new OA\Property(
            property: 'completed_at',
            type: 'string',
            format: 'date-time',
            nullable: true,
            example: '2026-09-01T10:00:15Z',
        ),
    ],
)]
class ImportSchemas
{
}
