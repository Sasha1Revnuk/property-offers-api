<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read array{message: string, data: mixed} $resource
 */
class SuccessResource extends JsonResource
{
    /**
     * @return array{success: true, message: string, data: mixed}
     */
    public function toArray(Request $request): array
    {
        return [
            'success' => true,
            'message' => $this->resource['message'],
            'data' => $this->resource['data'],
        ];
    }
}
