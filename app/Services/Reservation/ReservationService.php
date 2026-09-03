<?php

namespace App\Services\Reservation;

use App\Exceptions\OfferSoldOutException;
use App\Models\Offer;
use App\Models\Reservation;
use App\Services\Reservation\Contracts\ReservationServiceInterface;
use App\Services\Reservation\Dto\CreateReservationDto;
use App\Services\Reservation\Enumerators\ReservationStatusEnumerator;
use Illuminate\Support\Facades\DB;

class ReservationService implements ReservationServiceInterface
{
    public function reserve(Offer $offer, CreateReservationDto $dto): Reservation
    {
        return DB::transaction(function () use ($offer, $dto): Reservation {
            /** @var Offer $locked */
            $locked = Offer::query()->whereKey($offer->id)->lockForUpdate()->firstOrFail();

            $existing = Reservation::query()
                ->where('client_reference', $dto->clientReference)
                ->first();

            if ($existing !== null) {
                return $existing;
            }

            if ($locked->available_units < 1) {
                throw new OfferSoldOutException();
            }

            $locked->decrement('available_units');

            return Reservation::query()->create([
                'offer_id' => $locked->id,
                'client_reference' => $dto->clientReference,
                'customer_name' => $dto->customerName,
                'customer_email' => $dto->customerEmail,
                'status' => ReservationStatusEnumerator::Confirmed,
            ]);
        });
    }
}
