<?php

namespace Tests\Unit\Services\Reservation;

use App\Exceptions\OfferSoldOutException;
use App\Models\Offer;
use App\Models\Reservation;
use App\Services\Reservation\Contracts\ReservationServiceInterface;
use App\Services\Reservation\Dto\CreateReservationDto;
use App\Services\Reservation\Enumerators\ReservationStatusEnumerator;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReservationServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[Test]
    public function reserve_creates_confirmed_reservation_and_decrements_units(): void
    {
        $offer = Offer::factory()->create(['available_units' => 3]);

        $reservation = $this->service()->reserve($offer, $this->dto());

        $this->assertModelExists($reservation);
        $this->assertSame($offer->id, $reservation->offer_id);
        $this->assertSame('ref-001', $reservation->client_reference);
        $this->assertSame('Ada Lovelace', $reservation->customer_name);
        $this->assertSame('ada@example.com', $reservation->customer_email);
        $this->assertSame(ReservationStatusEnumerator::Confirmed, $reservation->status);

        $offer->refresh();
        $this->assertSame(2, $offer->available_units);
    }

    #[Test]
    public function reserve_throws_when_sold_out_and_does_not_go_negative(): void
    {
        $offer = Offer::factory()->create(['available_units' => 0]);

        try {
            $this->service()->reserve($offer, $this->dto());
            $this->fail('Expected OfferSoldOutException was not thrown.');
        } catch (OfferSoldOutException) {
            // expected
        }

        $offer->refresh();
        $this->assertSame(0, $offer->available_units);
        $this->assertSame(0, Reservation::query()->count());
    }

    #[Test]
    public function reserve_is_idempotent_for_same_client_reference(): void
    {
        $offer = Offer::factory()->create(['available_units' => 2]);
        $dto = $this->dto(clientReference: 'idempotent-ref');

        $first = $this->service()->reserve($offer, $dto);
        $second = $this->service()->reserve($offer, $dto);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, Reservation::query()->count());

        $offer->refresh();
        $this->assertSame(1, $offer->available_units);
    }

    private function service(): ReservationServiceInterface
    {
        return $this->app->make(ReservationServiceInterface::class);
    }

    private function dto(
        string $clientReference = 'ref-001',
        string $customerName = 'Ada Lovelace',
        string $customerEmail = 'ada@example.com',
    ): CreateReservationDto {
        return new CreateReservationDto(
            clientReference: $clientReference,
            customerName: $customerName,
            customerEmail: $customerEmail,
        );
    }
}
