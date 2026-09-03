<?php

namespace App\Services\Property\Dto;

use App\Http\Requests\Api\V1\Property\SearchPropertiesRequest;

readonly class SearchPropertiesDto
{
    public function __construct(
        public string $checkIn,
        public string $checkOut,
        public int $guests,
        public ?string $city = null,
        public int $page = 1,
        public int $perPage = 15,
    ) {
    }

    public static function fromRequest(SearchPropertiesRequest $request): self
    {
        /** @var array{
         *     check_in: string,
         *     check_out: string,
         *     guests: int|numeric-string,
         *     city?: string|null,
         *     page?: int|numeric-string,
         *     per_page?: int|numeric-string
         * } $validated
         */
        $validated = $request->validated();

        $city = $validated['city'] ?? null;
        if ($city === '') {
            $city = null;
        }

        return new self(
            checkIn: $validated['check_in'],
            checkOut: $validated['check_out'],
            guests: (int) $validated['guests'],
            city: $city,
            page: (int) ($validated['page'] ?? 1),
            perPage: (int) ($validated['per_page'] ?? 15),
        );
    }
}
