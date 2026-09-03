<?php

namespace App\Services\Api\Contracts;

use Illuminate\Http\JsonResponse;

interface ApiServiceInterface
{
    public function success(string $message, mixed $data = null, int $status = 200): JsonResponse;

    public function created(string $message, mixed $data = null): JsonResponse;

    public function accepted(string $message, mixed $data = null): JsonResponse;

    /**
     * @param  array<string, array<int, string>>  $errors
     */
    public function validationError(string $message, array $errors = []): JsonResponse;

    public function notFound(string $message): JsonResponse;

    /**
     * @param  array<string, array<int, string>>|null  $errors
     */
    public function conflict(string $message, ?array $errors = null): JsonResponse;

    public function serverError(string $internalMessage, string $clientMessage): JsonResponse;
}
