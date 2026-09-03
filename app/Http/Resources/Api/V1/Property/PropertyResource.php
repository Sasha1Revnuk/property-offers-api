<?php

namespace App\Http\Resources\Api\V1\Property;

use App\Support\ApiDateTimeFormatter;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyResource extends JsonResource
{
    /**
     * @return array{
     *     code: string,
     *     name: string,
     *     city: string,
     *     best_offer: array{
     *         id: int,
     *         supplier: string,
     *         price: int,
     *         currency: string,
     *         available_units: int,
     *         expires_at: string|null
     *     }
     * }
     */
    public function toArray(Request $request): array
    {
        /** @var object{
         *     code: string,
         *     name: string,
         *     city: string,
         *     offer_id: int|numeric-string,
         *     supplier_slug: string,
         *     price: int|numeric-string,
         *     currency: string,
         *     available_units: int|numeric-string,
         *     expires_at: mixed
         * } $row
         */
        $row = $this->resource;

        $expiresAt = $row->expires_at !== null
            ? ApiDateTimeFormatter::format(CarbonImmutable::parse($row->expires_at))
            : null;

        return [
            'code' => $row->code,
            'name' => $row->name,
            'city' => $row->city,
            'best_offer' => [
                'id' => (int) $row->offer_id,
                'supplier' => $row->supplier_slug,
                'price' => (int) $row->price,
                'currency' => $row->currency,
                'available_units' => (int) $row->available_units,
                'expires_at' => $expiresAt,
            ],
        ];
    }
}
