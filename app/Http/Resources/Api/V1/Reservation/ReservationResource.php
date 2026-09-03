<?php

namespace App\Http\Resources\Api\V1\Reservation;

use App\Http\Resources\Api\V1\EnumValueResource;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Reservation
 */
class ReservationResource extends JsonResource
{
    /**
     * @return array{
     *     id: int,
     *     offer_id: int,
     *     client_reference: string,
     *     customer_name: string,
     *     customer_email: string,
     *     status: array{value: string|int, label: string}
     * }
     */
    public function toArray(Request $request): array
    {
        /** @var Reservation $reservation */
        $reservation = $this->resource;

        /** @var array{value: string|int, label: string} $status */
        $status = (new EnumValueResource($reservation->status))->resolve();

        return [
            'id' => $reservation->id,
            'offer_id' => $reservation->offer_id,
            'client_reference' => $reservation->client_reference,
            'customer_name' => $reservation->customer_name,
            'customer_email' => $reservation->customer_email,
            'status' => $status,
        ];
    }
}
