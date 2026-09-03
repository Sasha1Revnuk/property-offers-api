<?php

namespace Tests\Unit\Services\Api;

use App\Http\Resources\Api\V1\SuccessResource;
use App\Services\Api\Contracts\ApiServiceInterface;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApiServiceTest extends TestCase
{
    private function api(): ApiServiceInterface
    {
        return $this->app->make(ApiServiceInterface::class);
    }

    /**
     * @return array<string, mixed>
     */
    private function responsePayload(mixed $responseData): array
    {
        /** @var array<string, mixed> $data */
        $data = $responseData;

        return $data;
    }

    #[Test]
    public function success_returns_envelope_with_status_200(): void
    {
        $response = $this->api()->success('Done.', ['id' => 1]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'success' => true,
            'message' => 'Done.',
            'data' => ['id' => 1],
        ], $this->responsePayload($response->getData(true)));
    }

    #[Test]
    public function created_returns_status_201(): void
    {
        $response = $this->api()->created('Created.', ['id' => 2]);
        $payload = $this->responsePayload($response->getData(true));

        $this->assertSame(201, $response->getStatusCode());
        $this->assertTrue($payload['success']);

        /** @var array{id: int} $data */
        $data = $payload['data'];
        $this->assertSame(2, $data['id']);
    }

    #[Test]
    public function accepted_returns_status_202(): void
    {
        $response = $this->api()->accepted('Accepted.', ['id' => 3]);
        $payload = $this->responsePayload($response->getData(true));

        $this->assertSame(202, $response->getStatusCode());
        $this->assertSame('Accepted.', $payload['message']);
    }

    #[Test]
    public function success_resolves_json_resource_data(): void
    {
        $response = $this->api()->success(
            'Wrapped.',
            new SuccessResource(['message' => 'inner', 'data' => ['ok' => true]]),
        );

        $this->assertSame([
            'success' => true,
            'message' => 'Wrapped.',
            'data' => [
                'success' => true,
                'message' => 'inner',
                'data' => ['ok' => true],
            ],
        ], $this->responsePayload($response->getData(true)));
    }

    #[Test]
    public function validation_error_returns_422_with_errors(): void
    {
        $response = $this->api()->validationError('Invalid.', [
            'email' => ['The email field is required.'],
        ]);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Invalid.',
            'errors' => [
                'email' => ['The email field is required.'],
            ],
        ], $this->responsePayload($response->getData(true)));
    }

    #[Test]
    public function not_found_returns_404_without_errors_key(): void
    {
        $response = $this->api()->notFound('Missing.');
        $payload = $this->responsePayload($response->getData(true));

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Missing.',
        ], $payload);
        $this->assertArrayNotHasKey('errors', $payload);
    }

    #[Test]
    public function conflict_returns_409(): void
    {
        $response = $this->api()->conflict('Sold out.');

        $this->assertSame(409, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Sold out.',
        ], $this->responsePayload($response->getData(true)));
    }

    #[Test]
    public function conflict_includes_errors_when_provided(): void
    {
        $response = $this->api()->conflict('Conflict.', [
            'offer' => ['Unavailable.'],
        ]);
        $payload = $this->responsePayload($response->getData(true));

        $this->assertSame(409, $response->getStatusCode());
        $this->assertSame(['offer' => ['Unavailable.']], $payload['errors']);
    }

    #[Test]
    public function server_error_logs_internal_message_and_returns_generic_client_message(): void
    {
        Log::shouldReceive('channel')
            ->once()
            ->with('api')
            ->andReturnSelf();
        Log::shouldReceive('error')
            ->once()
            ->with('secret internal failure');

        $response = $this->api()->serverError('secret internal failure', 'Something went wrong.');

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Something went wrong.',
        ], $this->responsePayload($response->getData(true)));
        $this->assertStringNotContainsString('secret', (string) $response->getContent());
    }
}
