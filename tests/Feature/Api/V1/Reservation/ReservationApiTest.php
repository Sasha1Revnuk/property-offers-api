<?php

namespace Tests\Feature\Api\V1\Reservation;

use App\Models\Offer;
use App\Support\TranslationHelper;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReservationApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[Test]
    public function store_creates_reservation_with_201_envelope(): void
    {
        $offer = Offer::factory()->create(['available_units' => 2]);

        $response = $this->postJson(
            "/api/v1/offers/{$offer->id}/reservations",
            $this->validPayload(),
        );

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', TranslationHelper::get('translations.reservation.created'))
            ->assertJsonPath('data.offer_id', $offer->id)
            ->assertJsonPath('data.client_reference', 'ref-001')
            ->assertJsonPath('data.customer_name', 'Ada Lovelace')
            ->assertJsonPath('data.customer_email', 'ada@example.com')
            ->assertJsonPath('data.status.value', 'confirmed')
            ->assertJsonPath('data.status.label', 'Confirmed')
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'offer_id',
                    'client_reference',
                    'customer_name',
                    'customer_email',
                    'status' => ['value', 'label'],
                ],
            ]);

        $offer->refresh();
        $this->assertSame(1, $offer->available_units);
    }

    #[Test]
    public function store_returns_409_when_offer_is_sold_out(): void
    {
        $offer = Offer::factory()->create(['available_units' => 0]);

        $response = $this->postJson(
            "/api/v1/offers/{$offer->id}/reservations",
            $this->validPayload(),
        );

        $response->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', TranslationHelper::get('translations.reservation.sold_out'));

        $offer->refresh();
        $this->assertSame(0, $offer->available_units);
    }

    #[Test]
    public function store_returns_404_for_missing_offer(): void
    {
        $response = $this->postJson(
            '/api/v1/offers/999999/reservations',
            $this->validPayload(),
        );

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', TranslationHelper::get('translations.common.not_found'));
    }

    #[Test]
    public function store_returns_422_for_invalid_payload(): void
    {
        $offer = Offer::factory()->create(['available_units' => 2]);

        $response = $this->postJson(
            "/api/v1/offers/{$offer->id}/reservations",
            [
                'client_reference' => 'ref-001',
            ],
        );

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors']);
    }

    /**
     * @return array{client_reference: string, customer_name: string, customer_email: string}
     */
    private function validPayload(): array
    {
        return [
            'client_reference' => 'ref-001',
            'customer_name' => 'Ada Lovelace',
            'customer_email' => 'ada@example.com',
        ];
    }
}
