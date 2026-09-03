<?php

namespace App\Services\Property;

use App\Models\Offer;
use App\Models\Property;
use App\Models\Supplier;
use App\Services\Property\Contracts\PropertySearchServiceInterface;
use App\Services\Property\Dto\SearchPropertiesDto;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;

class PropertySearchService implements PropertySearchServiceInterface
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
    public function search(SearchPropertiesDto $dto): Paginator
    {
        $offersTable = (new Offer())->getTable();
        $suppliersTable = (new Supplier())->getTable();
        $propertiesTable = (new Property())->getTable();

        $rankedOffers = Offer::query()
            ->join($suppliersTable, "{$suppliersTable}.id", '=', "{$offersTable}.supplier_id")
            ->whereDate("{$offersTable}.check_in", $dto->checkIn)
            ->whereDate("{$offersTable}.check_out", $dto->checkOut)
            ->where("{$offersTable}.max_guests", '>=', $dto->guests)
            ->where("{$offersTable}.available_units", '>', 0)
            ->where("{$offersTable}.expires_at", '>', now())
            ->selectRaw(
                "{$offersTable}.*, {$suppliersTable}.slug as supplier_slug, ".
                "ROW_NUMBER() OVER (PARTITION BY {$offersTable}.property_id ORDER BY {$offersTable}.price ASC, {$offersTable}.id ASC) as rn"
            );

        $query = DB::query()
            ->fromSub($rankedOffers, 'o')
            ->join($propertiesTable, "{$propertiesTable}.id", '=', 'o.property_id')
            ->when(
                $dto->city !== null,
                static fn ($q) => $q->where("{$propertiesTable}.city", $dto->city),
            )
            ->where('o.rn', 1)
            ->orderBy('o.price')
            ->select([
                "{$propertiesTable}.code",
                "{$propertiesTable}.name",
                "{$propertiesTable}.city",
                'o.id as offer_id',
                'o.supplier_slug',
                'o.price',
                'o.currency',
                'o.available_units',
                'o.expires_at',
            ]);

        /** @var Paginator<int, object{
         *     code: string,
         *     name: string,
         *     city: string,
         *     offer_id: int|numeric-string,
         *     supplier_slug: string,
         *     price: int|numeric-string,
         *     currency: string,
         *     available_units: int|numeric-string,
         *     expires_at: mixed
         * }> $paginator
         */
        $paginator = $query->simplePaginate(
            perPage: $dto->perPage,
            columns: ['*'],
            pageName: 'page',
            page: $dto->page,
        );

        return $paginator;
    }
}
