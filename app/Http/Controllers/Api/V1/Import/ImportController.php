<?php

namespace App\Http\Controllers\Api\V1\Import;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Import\StoreImportRequest;
use App\Http\Resources\Api\V1\Import\ImportAcceptedResource;
use App\Http\Resources\Api\V1\Import\ImportResource;
use App\Models\Import;
use App\Services\Api\Contracts\ApiServiceInterface;
use App\Services\Import\Contracts\ImportServiceInterface;
use App\Services\Import\Dto\CreateImportDto;
use App\Support\TranslationHelper;
use Illuminate\Http\JsonResponse;

class ImportController extends Controller
{
    public function __construct(
        private readonly ApiServiceInterface $apiService,
        private readonly ImportServiceInterface $importService,
    ) {
    }

    public function store(StoreImportRequest $request): JsonResponse
    {
        $import = $this->importService->createImport(
            CreateImportDto::fromRequest($request),
        );

        return $this->apiService->accepted(
            TranslationHelper::get('translations.import.accepted'),
            new ImportAcceptedResource($import),
        );
    }

    public function show(Import $import): JsonResponse
    {
        $import->load('supplier');

        return $this->apiService->success(
            TranslationHelper::get('translations.import.fetched'),
            new ImportResource($import),
        );
    }
}
