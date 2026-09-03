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
use OpenApi\Attributes as OA;

class ImportController extends Controller
{
    public function __construct(
        private readonly ApiServiceInterface $apiService,
        private readonly ImportServiceInterface $importService,
    ) {
    }

    #[OA\Post(
        path: '/api/v1/imports',
        operationId: 'api.v1.imports.store',
        summary: 'Accept a supplier import',
        description: <<<'MD'
Accepts an async offer import for a supplier. Idempotent on `(supplier, external_import_id)`:
a repeat request returns the same import and does not re-dispatch the job.

`status` uses ImportStatusEnumValue (`pending` on accept). Processing continues in a queue worker.
MD,
        tags: ['Imports'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StoreImportRequest'),
        ),
        responses: [
            new OA\Response(
                response: 202,
                description: 'Import accepted',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
                        new OA\Schema(properties: [
                            new OA\Property(property: 'data', ref: '#/components/schemas/ImportAccepted'),
                        ]),
                    ],
                ),
            ),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationError'),
            new OA\Response(response: 429, ref: '#/components/responses/TooManyRequests'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
        ],
    )]
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

    #[OA\Get(
        path: '/api/v1/imports/{import}',
        operationId: 'api.v1.imports.show',
        summary: 'Get import status',
        description: <<<'MD'
Returns the current state of an import, including progress counters and `status`
(ImportStatusEnumValue: `pending`, `processing`, `completed`, `failed`).
MD,
        tags: ['Imports'],
        parameters: [
            new OA\Parameter(
                name: 'import',
                description: 'Import id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 15),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Import status',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: '#/components/schemas/SuccessEnvelope'),
                        new OA\Schema(properties: [
                            new OA\Property(property: 'data', ref: '#/components/schemas/Import'),
                        ]),
                    ],
                ),
            ),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 429, ref: '#/components/responses/TooManyRequests'),
            new OA\Response(response: 500, ref: '#/components/responses/ServerError'),
        ],
    )]
    public function show(Import $import): JsonResponse
    {
        $import->load('supplier');

        return $this->apiService->success(
            TranslationHelper::get('translations.import.fetched'),
            new ImportResource($import),
        );
    }
}
