<?php

namespace Tests\Unit\Services\Property;

use App\Models\Import;
use App\Models\Offer;
use App\Models\Property;
use App\Models\Supplier;
use App\Services\Property\Contracts\PropertySearchServiceInterface;
use App\Services\Property\Dto\SearchPropertiesDto;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Pagination\Paginator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PropertySearchServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const CHECK_IN = '2026-10-01';

    private const CHECK_OUT = '2026-10-05';

    #[Test]
    public function search_selects_cheapest_offer_per_property(): void
    {
        $supplier = Supplier::factory()->create(['slug' => 'supplier-a']);
        $import = Import::factory()->for($supplier)->create();
        $property = Property::factory()->create([
            'code' => 'BCN-0001',
            'city' => 'Barcelona',
        ]);

        $expensive = $this->createOffer($import, $property, price: 90_000);
        $cheap = $this->createOffer($import, $property, price: 50_000);
        $this->createOffer($import, $property, price: 70_000);

        $paginator = $this->service()->search($this->dto());

        $items = $this->searchItems($paginator);
        $this->assertCount(1, $items);
        $row = $items[0];
        $this->assertSame('BCN-0001', $row->code);
        $this->assertSame($cheap->id, (int) $row->offer_id);
        $this->assertSame(50_000, (int) $row->price);
        $this->assertNotSame($expensive->id, (int) $row->offer_id);
    }

    #[Test]
    public function search_filters_by_city(): void
    {
        $supplier = Supplier::factory()->create(['slug' => 'supplier-a']);
        $import = Import::factory()->for($supplier)->create();

        $barcelona = Property::factory()->create(['code' => 'BCN-0001', 'city' => 'Barcelona']);
        $madrid = Property::factory()->create(['code' => 'MAD-0001', 'city' => 'Madrid']);

        $this->createOffer($import, $barcelona, price: 40_000);
        $this->createOffer($import, $madrid, price: 30_000);

        $paginator = $this->service()->search($this->dto(city: 'Barcelona'));

        $items = $this->searchItems($paginator);
        $this->assertCount(1, $items);
        $this->assertSame('BCN-0001', $items[0]->code);
    }

    #[Test]
    public function search_excludes_offers_with_insufficient_guests(): void
    {
        $supplier = Supplier::factory()->create(['slug' => 'supplier-a']);
        $import = Import::factory()->for($supplier)->create();
        $property = Property::factory()->create(['city' => 'Barcelona']);

        $this->createOffer($import, $property, price: 40_000, maxGuests: 1);

        $paginator = $this->service()->search($this->dto(guests: 2));

        $this->assertCount(0, $paginator->items());
    }

    #[Test]
    public function search_excludes_offers_with_zero_available_units(): void
    {
        $supplier = Supplier::factory()->create(['slug' => 'supplier-a']);
        $import = Import::factory()->for($supplier)->create();
        $property = Property::factory()->create(['city' => 'Barcelona']);

        $this->createOffer($import, $property, price: 40_000, availableUnits: 0);

        $paginator = $this->service()->search($this->dto());

        $this->assertCount(0, $paginator->items());
    }

    #[Test]
    public function search_excludes_expired_offers(): void
    {
        $supplier = Supplier::factory()->create(['slug' => 'supplier-a']);
        $import = Import::factory()->for($supplier)->create();
        $property = Property::factory()->create(['city' => 'Barcelona']);

        $this->createOffer($import, $property, price: 40_000, expiresAt: now()->subDay());

        $paginator = $this->service()->search($this->dto());

        $this->assertCount(0, $paginator->items());
    }

    #[Test]
    public function search_orders_properties_by_best_offer_price_ascending(): void
    {
        $supplier = Supplier::factory()->create(['slug' => 'supplier-a']);
        $import = Import::factory()->for($supplier)->create();

        $expensiveProperty = Property::factory()->create(['code' => 'EXP-1', 'city' => 'Barcelona']);
        $cheapProperty = Property::factory()->create(['code' => 'CHP-1', 'city' => 'Barcelona']);

        $this->createOffer($import, $expensiveProperty, price: 80_000);
        $this->createOffer($import, $cheapProperty, price: 20_000);

        $paginator = $this->service()->search($this->dto());

        $items = $this->searchItems($paginator);
        $this->assertCount(2, $items);
        $this->assertSame('CHP-1', $items[0]->code);
        $this->assertSame('EXP-1', $items[1]->code);
    }

    #[Test]
    public function search_returns_empty_page_when_no_matches(): void
    {
        $paginator = $this->service()->search($this->dto());

        $this->assertCount(0, $paginator->items());
        $this->assertSame(1, $paginator->currentPage());
    }

    private function service(): PropertySearchServiceInterface
    {
        return $this->app->make(PropertySearchServiceInterface::class);
    }

    /**
     * @param  Paginator<int, object{
     *     code: string,
     *     name: string,
     *     city: string,
     *     offer_id: int|numeric-string,
     *     supplier_slug: string,
     *     price: int|numeric-string,
     *     currency: string,
     *     available_units: int|numeric-string,
     *     expires_at: mixed
     * }>  $paginator
     * @return list<object{
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
    private function searchItems(Paginator $paginator): array
    {
        return array_values($paginator->items());
    }

    private function dto(
        int $guests = 2,
        ?string $city = null,
        int $page = 1,
        int $perPage = 15,
    ): SearchPropertiesDto {
        return new SearchPropertiesDto(
            checkIn: self::CHECK_IN,
            checkOut: self::CHECK_OUT,
            guests: $guests,
            city: $city,
            page: $page,
            perPage: $perPage,
        );
    }

    private function createOffer(
        Import $import,
        Property $property,
        int $price,
        int $maxGuests = 4,
        int $availableUnits = 2,
        mixed $expiresAt = null,
    ): Offer {
        return Offer::factory()->create([
            'import_id' => $import->id,
            'supplier_id' => $import->supplier_id,
            'property_id' => $property->id,
            'check_in' => self::CHECK_IN,
            'check_out' => self::CHECK_OUT,
            'max_guests' => $maxGuests,
            'price' => $price,
            'currency' => 'EUR',
            'available_units' => $availableUnits,
            'expires_at' => $expiresAt ?? now()->addDays(30),
        ]);
    }
}
