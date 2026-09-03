<?php

namespace App\Http\Controllers\Api\V1\Reservation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Reservation\StoreReservationRequest;
use App\Http\Resources\Api\V1\Reservation\ReservationResource;
use App\Models\Offer;
use App\Services\Api\Contracts\ApiServiceInterface;
use App\Services\Reservation\Contracts\ReservationServiceInterface;
use App\Services\Reservation\Dto\CreateReservationDto;
use App\Support\TranslationHelper;
use Illuminate\Http\JsonResponse;

class ReservationController extends Controller
{
    public function __construct(
        private readonly ApiServiceInterface $apiService,
        private readonly ReservationServiceInterface $reservationService,
    ) {
    }

    public function store(StoreReservationRequest $request, Offer $offer): JsonResponse
    {
        $reservation = $this->reservationService->reserve(
            $offer,
            CreateReservationDto::fromRequest($request),
        );

        return $this->apiService->created(
            TranslationHelper::get('translations.reservation.created'),
            new ReservationResource($reservation),
        );
    }
}
