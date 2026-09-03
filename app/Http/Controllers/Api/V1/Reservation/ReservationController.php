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
use OpenApi\Attributes as OA;

class ReservationController extends Controller
{
    public function __construct(
        private readonly ApiServiceInterface $apiService,
        private readonly ReservationServiceInterface $reservationService,
    ) {
    }

    #[OA\Post(
        path: '/api/v1/offers/{offer}/reservations',
        operationId: 'api.v1.offers.reservations.store',
        summary: 'Reserve an offer unit',
        description: <<<'MD'
Creates a reservation for an offer under a row lock. Idempotent on `client_reference`:
a repeat request returns the existing reservation.

`status` uses ReservationStatusEnumValue (`confirmed`). Returns 409 Conflict when
`available_units` is zero (sold out).
MD,
        tags: ['Reservations'],
        parameters: [
            new OA\Parameter(
                name: 'offer',
                description: 'Offer id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 125),
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StoreReservationRequest'),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Reservation created',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
                        new OA\Schema(properties: [
                            new OA\Property(property: 'data', ref: '#/components/schemas/Reservation'),
                        ]),
                    ],
                ),
            ),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 409, ref: '#/components/responses/Conflict'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
            new OA\Response(response: 429, ref: '#/components/responses/TooManyRequests'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
        ],
    )]
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
