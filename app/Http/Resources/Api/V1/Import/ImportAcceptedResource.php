<?php

namespace App\Http\Resources\Api\V1\Import;

use App\Http\Resources\Api\V1\EnumValueResource;
use App\Models\Import;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Import
 */
class ImportAcceptedResource extends JsonResource
{
    /**
     * @return array{id: int, status: array{value: string|int, label: string}}
     */
    public function toArray(Request $request): array
    {
        /** @var Import $import */
        $import = $this->resource;

        /** @var array{value: string|int, label: string} $status */
        $status = (new EnumValueResource($import->status))->resolve();

        return [
            'id' => $import->id,
            'status' => $status,
        ];
    }
}
