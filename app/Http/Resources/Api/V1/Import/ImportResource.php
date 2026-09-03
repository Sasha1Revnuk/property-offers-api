<?php

namespace App\Http\Resources\Api\V1\Import;

use App\Http\Resources\Api\V1\EnumValueResource;
use App\Models\Import;
use App\Models\Supplier;
use App\Support\ApiDateTimeFormatter;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use RuntimeException;

/**
 * @mixin Import
 */
class ImportResource extends JsonResource
{
    /**
     * @return array{
     *     id: int,
     *     supplier: string,
     *     external_import_id: string,
     *     sent_at: string|null,
     *     status: array{value: string|int, label: string},
     *     total_offers: int,
     *     processed_offers: int,
     *     error: string|null,
     *     created_at: string|null,
     *     completed_at: string|null
     * }
     */
    public function toArray(Request $request): array
    {
        /** @var Import $import */
        $import = $this->resource;

        $supplier = $import->supplier;

        if (! $supplier instanceof Supplier) {
            throw new RuntimeException('Import supplier relation must be loaded.');
        }

        /** @var array{value: string|int, label: string} $status */
        $status = (new EnumValueResource($import->status))->resolve();

        return [
            'id' => $import->id,
            'supplier' => $supplier->slug,
            'external_import_id' => $import->external_import_id,
            'sent_at' => ApiDateTimeFormatter::format(CarbonImmutable::parse($import->sent_at)),
            'status' => $status,
            'total_offers' => $import->total_offers,
            'processed_offers' => $import->processed_offers,
            'error' => $import->error,
            'created_at' => $import->created_at !== null
                ? ApiDateTimeFormatter::format(CarbonImmutable::parse($import->created_at))
                : null,
            'completed_at' => $import->completed_at !== null
                ? ApiDateTimeFormatter::format(CarbonImmutable::parse($import->completed_at))
                : null,
        ];
    }
}
