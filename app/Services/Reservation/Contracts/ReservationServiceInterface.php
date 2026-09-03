<?php

namespace App\Services\Reservation\Contracts;

use App\Models\Offer;
use App\Models\Reservation;
use App\Services\Reservation\Dto\CreateReservationDto;

interface ReservationServiceInterface
{
    public function reserve(Offer $offer, CreateReservationDto $dto): Reservation;
}
