<?php

namespace App\Http\Controllers\Api\V1\Property;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Property\SearchPropertiesRequest;
use App\Http\Resources\Api\V1\PaginatedResource;
use App\Http\Resources\Api\V1\Property\PropertyResource;
use App\Services\Api\Contracts\ApiServiceInterface;
use App\Services\Property\Contracts\PropertySearchServiceInterface;
use App\Services\Property\Dto\SearchPropertiesDto;
use App\Support\TranslationHelper;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class PropertyController extends Controller
{
    public function __construct(
        private readonly ApiServiceInterface $apiService,
        private readonly PropertySearchServiceInterface $propertySearchService,
    ) {
    }

    #[OA\Get(
        path: '/api/v1/properties',
        operationId: 'api.v1.properties.index',
        summary: 'Search properties by cheapest offer',
        description: <<<'MD'
Returns properties that have at least one matching offer, each with its cheapest
`best_offer`. Filtering (dates, guests, availability, expiry, optional city),
cheapest selection, sorting, and pagination all run in the database.

Paginated `data` shape: `{ items, pagination }` with `next` / `prev` URLs.
MD,
        tags: ['Properties'],
        parameters: [
            new OA\Parameter(
                name: 'check_in',
                description: 'Required check-in date (Y-m-d)',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'date', example: '2026-09-10'),
            ),
            new OA\Parameter(
                name: 'check_out',
                description: 'Required check-out date (Y-m-d), after check_in',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'date', example: '2026-09-15'),
            ),
            new OA\Parameter(
                name: 'guests',
                description: 'Required guest count (min 1)',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'integer', minimum: 1, example: 2),
            ),
            new OA\Parameter(
                name: 'city',
                description: 'Optional exact city filter',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', example: 'Barcelona'),
            ),
            new OA\Parameter(ref: '#/components/parameters/Page'),
            new OA\Parameter(ref: '#/components/parameters/PerPage'),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Property list',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
                        new OA\Schema(properties: [
                            new OA\Property(property: 'data', ref: '#/components/schemas/PropertyListData'),
                        ]),
                    ],
                ),
            ),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
            new OA\Response(response: 429, ref: '#/components/responses/TooManyRequests'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
        ],
    )]
    public function index(SearchPropertiesRequest $request): JsonResponse
    {
        $paginator = $this->propertySearchService->search(
            SearchPropertiesDto::fromRequest($request),
        );

        return $this->apiService->success(
            TranslationHelper::get('translations.property.list'),
            new PaginatedResource([
                'items' => PropertyResource::collection($paginator->items()),
                'paginator' => $paginator,
            ]),
        );
    }
}
