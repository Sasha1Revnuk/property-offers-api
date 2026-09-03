<?php

namespace Tests\Feature\Api\V1\Property;

use App\Models\Import;
use App\Models\Offer;
use App\Models\Property;
use App\Models\Supplier;
use App\Support\TranslationHelper;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PropertySearchApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const CHECK_IN = '2026-10-01';

    private const CHECK_OUT = '2026-10-05';

    #[Test]
    public function index_returns_envelope_with_best_offer(): void
    {
        $supplier = Supplier::factory()->create(['slug' => 'supplier-a']);
        $import = Import::factory()->for($supplier)->create();
        $property = Property::factory()->create([
            'code' => 'BCN-0001',
            'name' => 'Barcelona Apartment',
            'city' => 'Barcelona',
        ]);
        $offer = $this->createOffer($import, $property, price: 72_500);

        $response = $this->getJson($this->searchUrl([
            'city' => 'Barcelona',
            'guests' => 2,
        ]));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', TranslationHelper::get('translations.property.list'))
            ->assertJsonPath('data.items.0.code', 'BCN-0001')
            ->assertJsonPath('data.items.0.name', 'Barcelona Apartment')
            ->assertJsonPath('data.items.0.city', 'Barcelona')
            ->assertJsonPath('data.items.0.best_offer.id', $offer->id)
            ->assertJsonPath('data.items.0.best_offer.supplier', 'supplier-a')
            ->assertJsonPath('data.items.0.best_offer.price', 72_500)
            ->assertJsonPath('data.items.0.best_offer.currency', 'EUR')
            ->assertJsonPath('data.items.0.best_offer.available_units', 2)
            ->assertJsonStructure([
                'data' => [
                    'items' => [
                        [
                            'code',
                            'name',
                            'city',
                            'best_offer' => [
                                'id',
                                'supplier',
                                'price',
                                'currency',
                                'available_units',
                                'expires_at',
                            ],
                        ],
                    ],
                    'pagination' => [
                        'current_page',
                        'per_page',
                        'total',
                        'last_page',
                        'next',
                        'prev',
                    ],
                ],
            ]);

        $this->assertNull($response->json('data.pagination.total'));
        $this->assertNull($response->json('data.pagination.last_page'));
        $this->assertNotNull($response->json('data.items.0.best_offer.expires_at'));
    }

    #[Test]
    public function index_pagination_exposes_next_prev_and_per_page(): void
    {
        $supplier = Supplier::factory()->create(['slug' => 'supplier-a']);
        $import = Import::factory()->for($supplier)->create();

        foreach (['P-1', 'P-2', 'P-3'] as $index => $code) {
            $property = Property::factory()->create([
                'code' => $code,
                'city' => 'Barcelona',
            ]);
            $this->createOffer($import, $property, price: 10_000 + ($index * 1_000));
        }

        $pageOne = $this->getJson($this->searchUrl([
            'city' => 'Barcelona',
            'guests' => 2,
            'per_page' => 2,
            'page' => 1,
        ]));

        $pageOne->assertOk()
            ->assertJsonPath('data.pagination.current_page', 1)
            ->assertJsonPath('data.pagination.per_page', 2)
            ->assertJsonPath('data.pagination.prev', null)
            ->assertJsonCount(2, 'data.items');

        $nextUrl = $pageOne->json('data.pagination.next');
        $this->assertIsString($nextUrl);
        $this->assertStringContainsString('page=2', $nextUrl);

        $pageTwo = $this->getJson($this->searchUrl([
            'city' => 'Barcelona',
            'guests' => 2,
            'per_page' => 2,
            'page' => 2,
        ]));

        $pageTwo->assertOk()
            ->assertJsonPath('data.pagination.current_page', 2)
            ->assertJsonPath('data.pagination.per_page', 2)
            ->assertJsonCount(1, 'data.items');

        $prevUrl = $pageTwo->json('data.pagination.prev');
        $this->assertIsString($prevUrl);
        $this->assertStringContainsString('page=1', $prevUrl);
        $this->assertNull($pageTwo->json('data.pagination.next'));
    }

    #[Test]
    public function index_returns_422_when_required_query_params_missing(): void
    {
        $response = $this->getJson('/api/v1/properties');

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', TranslationHelper::get('translations.common.validation_error'))
            ->assertJsonStructure(['errors' => ['check_in', 'check_out', 'guests']]);
    }

    #[Test]
    public function index_returns_empty_items_when_no_matches(): void
    {
        $response = $this->getJson($this->searchUrl([
            'city' => 'Barcelona',
            'guests' => 2,
        ]));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.items', [])
            ->assertJsonPath('data.pagination.current_page', 1)
            ->assertJsonPath('data.pagination.per_page', 15)
            ->assertJsonPath('data.pagination.next', null)
            ->assertJsonPath('data.pagination.prev', null);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function searchUrl(array $query = []): string
    {
        return '/api/v1/properties?'.http_build_query(array_merge([
            'check_in' => self::CHECK_IN,
            'check_out' => self::CHECK_OUT,
        ], $query));
    }

    private function createOffer(Import $import, Property $property, int $price): Offer
    {
        return Offer::factory()->create([
            'import_id' => $import->id,
            'supplier_id' => $import->supplier_id,
            'property_id' => $property->id,
            'check_in' => self::CHECK_IN,
            'check_out' => self::CHECK_OUT,
            'max_guests' => 4,
            'price' => $price,
            'currency' => 'EUR',
            'available_units' => 2,
            'expires_at' => now()->addDays(30),
        ]);
    }
}
