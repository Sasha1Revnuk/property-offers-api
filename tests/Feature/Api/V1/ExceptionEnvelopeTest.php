<?php

namespace Tests\Feature\Api\V1;

use App\Exceptions\OfferSoldOutException;
use App\Support\TranslationHelper;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class ExceptionEnvelopeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('api')->prefix('api/v1')->group(function (): void {
            Route::post('/__test/validation', function (): never {
                throw ValidationException::withMessages([
                    'name' => ['The name field is required.'],
                ]);
            });

            Route::get('/__test/model-not-found', function (): never {
                throw (new ModelNotFoundException())->setModel('App\\Models\\Offer', 999);
            });

            Route::get('/__test/not-found', function (): never {
                abort(404);
            });

            Route::post('/__test/sold-out', function (): never {
                throw new OfferSoldOutException();
            });

            Route::get('/__test/server-error', function (): never {
                throw new RuntimeException('do not leak this detail');
            });
        });
    }

    #[Test]
    public function validation_exception_returns_422_envelope(): void
    {
        $response = $this->postJson('/api/v1/__test/validation');

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', TranslationHelper::get('translations.common.validation_error'))
            ->assertJsonPath('errors.name.0', 'The name field is required.');
    }

    #[Test]
    public function model_not_found_returns_404_envelope(): void
    {
        $response = $this->getJson('/api/v1/__test/model-not-found');

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', TranslationHelper::get('translations.common.not_found'))
            ->assertJsonMissingPath('errors');
    }

    #[Test]
    public function not_found_http_exception_returns_404_envelope(): void
    {
        $response = $this->getJson('/api/v1/__test/not-found');

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', TranslationHelper::get('translations.common.not_found'));
    }

    #[Test]
    public function offer_sold_out_returns_409_envelope(): void
    {
        $response = $this->postJson('/api/v1/__test/sold-out');

        $response->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', TranslationHelper::get('translations.reservation.sold_out'));
    }

    #[Test]
    public function unexpected_exception_returns_500_envelope_without_internal_message(): void
    {
        $response = $this->getJson('/api/v1/__test/server-error');

        $response->assertStatus(500)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', TranslationHelper::get('translations.common.server_error'));

        $this->assertStringNotContainsString(
            'do not leak this detail',
            $response->getContent(),
        );
    }
}
