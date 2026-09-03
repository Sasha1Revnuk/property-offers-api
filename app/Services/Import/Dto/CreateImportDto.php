<?php

namespace App\Services\Import\Dto;

use App\Http\Requests\Api\V1\Import\StoreImportRequest;

readonly class CreateImportDto
{
    /**
     * @param  list<ImportOfferDto>  $offers
     */
    public function __construct(
        public string $supplier,
        public string $externalImportId,
        public string $sentAt,
        public array $offers,
    ) {
    }

    public static function fromRequest(StoreImportRequest $request): self
    {
        /** @var array{
         *     supplier: string,
         *     external_import_id: string,
         *     sent_at: string,
         *     offers: list<array{
         *         external_id: string,
         *         property: array{code: string, name: string, city: string},
         *         check_in: string,
         *         check_out: string,
         *         max_guests: int,
         *         price: int,
         *         currency: string,
         *         available_units: int,
         *         expires_at: string
         *     }>
         * } $validated
         */
        $validated = $request->validated();

        $offers = array_map(
            static fn (array $offer): ImportOfferDto => ImportOfferDto::fromArray($offer),
            $validated['offers'],
        );

        return new self(
            supplier: $validated['supplier'],
            externalImportId: $validated['external_import_id'],
            sentAt: $validated['sent_at'],
            offers: $offers,
        );
    }

    /**
     * @return list<array{
     *     external_id: string,
     *     property: array{code: string, name: string, city: string},
     *     check_in: string,
     *     check_out: string,
     *     max_guests: int,
     *     price: int,
     *     currency: string,
     *     available_units: int,
     *     expires_at: string
     * }>
     */
    public function offersToArray(): array
    {
        return array_map(
            static fn (ImportOfferDto $offer): array => $offer->toArray(),
            $this->offers,
        );
    }
}
