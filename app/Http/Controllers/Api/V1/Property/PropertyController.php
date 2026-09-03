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

class PropertyController extends Controller
{
    public function __construct(
        private readonly ApiServiceInterface $apiService,
        private readonly PropertySearchServiceInterface $propertySearchService,
    ) {
    }

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
