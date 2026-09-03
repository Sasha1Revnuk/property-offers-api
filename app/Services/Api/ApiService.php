<?php

namespace App\Services\Api;

use App\Http\Resources\Api\V1\ErrorResource;
use App\Http\Resources\Api\V1\SuccessResource;
use App\Services\Api\Contracts\ApiServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class ApiService implements ApiServiceInterface
{
    public function success(string $message, mixed $data = null, int $status = 200): JsonResponse
    {
        return $this->successResponse($message, $data, $status);
    }

    public function created(string $message, mixed $data = null): JsonResponse
    {
        return $this->successResponse($message, $data, 201);
    }

    public function accepted(string $message, mixed $data = null): JsonResponse
    {
        return $this->successResponse($message, $data, 202);
    }

    /**
     * @param  array<string, array<int, string>>  $errors
     */
    public function validationError(string $message, array $errors = []): JsonResponse
    {
        return $this->errorResponse($message, $errors, 422);
    }

    public function notFound(string $message): JsonResponse
    {
        return $this->errorResponse($message, null, 404);
    }

    /**
     * @param  array<string, array<int, string>>|null  $errors
     */
    public function conflict(string $message, ?array $errors = null): JsonResponse
    {
        return $this->errorResponse($message, $errors, 409);
    }

    public function serverError(string $internalMessage, string $clientMessage): JsonResponse
    {
        Log::channel('api')->error($internalMessage);

        return $this->errorResponse($clientMessage, null, 500);
    }

    private function successResponse(string $message, mixed $data, int $status): JsonResponse
    {
        return (new SuccessResource([
            'message' => $message,
            'data' => $this->resolveData($data),
        ]))->response()->setStatusCode($status);
    }

    /**
     * @param  array<string, array<int, string>>|null  $errors
     */
    private function errorResponse(string $message, ?array $errors, int $status): JsonResponse
    {
        $payload = ['message' => $message];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        return (new ErrorResource($payload))->response()->setStatusCode($status);
    }

    private function resolveData(mixed $data): mixed
    {
        if ($data instanceof JsonResource) {
            return $data->resolve();
        }

        return $data;
    }
}
