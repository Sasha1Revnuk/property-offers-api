<?php

namespace App\OpenApi\Schemas\Reservation;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'StoreReservationRequest',
    required: ['client_reference', 'customer_name', 'customer_email'],
    properties: [
        new OA\Property(property: 'client_reference', type: 'string', example: 'order-abc-123'),
        new OA\Property(property: 'customer_name', type: 'string', example: 'Jane Doe'),
        new OA\Property(property: 'customer_email', type: 'string', format: 'email', example: 'jane@example.com'),
    ],
)]
#[OA\Schema(
    schema: 'Reservation',
    required: [
        'id',
        'offer_id',
        'client_reference',
        'customer_name',
        'customer_email',
        'status',
    ],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'offer_id', type: 'integer', example: 125),
        new OA\Property(property: 'client_reference', type: 'string', example: 'order-abc-123'),
        new OA\Property(property: 'customer_name', type: 'string', example: 'Jane Doe'),
        new OA\Property(property: 'customer_email', type: 'string', format: 'email', example: 'jane@example.com'),
        new OA\Property(property: 'status', ref: '#/components/schemas/ReservationStatusEnumValue'),
    ],
)]
class ReservationSchemas
{
}
