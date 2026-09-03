<?php

namespace App\Services\Reservation\Dto;

use App\Http\Requests\Api\V1\Reservation\StoreReservationRequest;

readonly class CreateReservationDto
{
    public function __construct(
        public string $clientReference,
        public string $customerName,
        public string $customerEmail,
    ) {
    }

    public static function fromRequest(StoreReservationRequest $request): self
    {
        /** @var array{client_reference: string, customer_name: string, customer_email: string} $validated */
        $validated = $request->validated();

        return new self(
            clientReference: $validated['client_reference'],
            customerName: $validated['customer_name'],
            customerEmail: $validated['customer_email'],
        );
    }
}
