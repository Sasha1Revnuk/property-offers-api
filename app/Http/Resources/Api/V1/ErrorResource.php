<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read array{message: string, errors?: array<string, array<int, string>>|null} $resource
 */
class ErrorResource extends JsonResource
{
    /**
     * @return array{success: false, message: string, errors?: array<string, array<int, string>>}
     */
    public function toArray(Request $request): array
    {
        $payload = [
            'success' => false,
            'message' => $this->resource['message'],
        ];

        if (array_key_exists('errors', $this->resource) && $this->resource['errors'] !== null) {
            $payload['errors'] = $this->resource['errors'];
        }

        return $payload;
    }
}
