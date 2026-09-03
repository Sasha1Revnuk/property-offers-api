<?php

namespace App\Services\Property\Contracts;

use App\Services\Property\Dto\SearchPropertiesDto;
use Illuminate\Pagination\Paginator;

interface PropertySearchServiceInterface
{
    /**
     * @return Paginator<int, object{
     *     code: string,
     *     name: string,
     *     city: string,
     *     offer_id: int|numeric-string,
     *     supplier_slug: string,
     *     price: int|numeric-string,
     *     currency: string,
     *     available_units: int|numeric-string,
     *     expires_at: mixed
     * }>
     */
    public function search(SearchPropertiesDto $dto): Paginator;
}
