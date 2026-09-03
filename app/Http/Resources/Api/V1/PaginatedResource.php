<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use InvalidArgumentException;

class PaginatedResource extends JsonResource
{
    /**
     * @return array{
     *     items: mixed,
     *     pagination: array{
     *         current_page: int,
     *         per_page: int,
     *         total: int|null,
     *         last_page: int|null,
     *         next: string|null,
     *         prev: string|null
     *     }
     * }
     */
    public function toArray(Request $request): array
    {
        if (! is_array($this->resource)) {
            throw new InvalidArgumentException('PaginatedResource requires an array resource.');
        }

        /** @var array{items?: mixed, paginator?: mixed} $payload */
        $payload = $this->resource;

        $paginator = $payload['paginator'] ?? null;
        $items = $payload['items'] ?? null;

        if (! $paginator instanceof LengthAwarePaginator && ! $paginator instanceof Paginator) {
            throw new InvalidArgumentException('PaginatedResource requires a paginator.');
        }

        if ($items instanceof JsonResource) {
            $items = $items->resolve($request);
        }

        $total = null;
        $lastPage = null;

        if ($paginator instanceof LengthAwarePaginator) {
            $total = $paginator->total();
            $lastPage = $paginator->lastPage();
        }

        return [
            'items' => $items,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $total,
                'last_page' => $lastPage,
                'next' => $paginator->nextPageUrl(),
                'prev' => $paginator->previousPageUrl(),
            ],
        ];
    }
}
