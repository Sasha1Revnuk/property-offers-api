<?php

namespace Tests\Feature\Api\V1\Import;

use App\Jobs\ProcessImportJob;
use App\Models\Import;
use App\Models\Supplier;
use App\Services\Import\Enumerators\ImportStatusEnumerator;
use App\Support\TranslationHelper;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ImportApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[Test]
    public function store_accepts_import_with_202_and_dispatches_job_once(): void
    {
        Queue::fake();
        Supplier::factory()->create(['slug' => 'supplier-a']);

        $payload = $this->validPayload();

        $response = $this->postJson('/api/v1/imports', $payload);

        $response->assertStatus(202)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', TranslationHelper::get('translations.import.accepted'))
            ->assertJsonPath('data.status.value', 'pending')
            ->assertJsonPath('data.status.label', 'Pending')
            ->assertJsonStructure(['data' => ['id', 'status' => ['value', 'label']]]);

        $importId = $response->json('data.id');
        $this->assertIsInt($importId);

        Queue::assertPushed(ProcessImportJob::class, 1);
        Queue::assertPushed(ProcessImportJob::class, function (ProcessImportJob $job) use ($importId): bool {
            return $job->importId === $importId;
        });
    }

    #[Test]
    public function store_is_idempotent_and_does_not_redispatch_job(): void
    {
        Queue::fake();
        Supplier::factory()->create(['slug' => 'supplier-a']);

        $payload = $this->validPayload();

        $first = $this->postJson('/api/v1/imports', $payload);
        $first->assertStatus(202);
        $importId = $first->json('data.id');

        /** @var Import $import */
        $import = Import::query()->findOrFail($importId);
        $import->update([
            'status' => ImportStatusEnumerator::Processing,
        ]);

        $second = $this->postJson('/api/v1/imports', $payload);

        $second->assertStatus(202)
            ->assertJsonPath('data.id', $importId)
            ->assertJsonPath('data.status.value', 'processing');

        Queue::assertPushed(ProcessImportJob::class, 1);
    }

    #[Test]
    public function store_returns_422_for_invalid_payload(): void
    {
        Supplier::factory()->create(['slug' => 'supplier-a']);

        $response = $this->postJson('/api/v1/imports', [
            'supplier' => 'supplier-a',
            'external_import_id' => 'imp-001',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', TranslationHelper::get('translations.common.validation_error'))
            ->assertJsonStructure(['errors']);
    }

    #[Test]
    public function show_returns_full_import_resource(): void
    {
        $supplier = Supplier::factory()->create(['slug' => 'supplier-a']);
        $import = Import::factory()->create([
            'supplier_id' => $supplier->id,
            'external_import_id' => 'imp-show-1',
            'status' => ImportStatusEnumerator::Pending,
            'total_offers' => 3,
            'processed_offers' => 1,
            'error' => null,
        ]);

        $response = $this->getJson('/api/v1/imports/'.$import->id);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', TranslationHelper::get('translations.import.fetched'))
            ->assertJsonPath('data.id', $import->id)
            ->assertJsonPath('data.supplier', 'supplier-a')
            ->assertJsonPath('data.external_import_id', 'imp-show-1')
            ->assertJsonPath('data.status.value', 'pending')
            ->assertJsonPath('data.status.label', 'Pending')
            ->assertJsonPath('data.total_offers', 3)
            ->assertJsonPath('data.processed_offers', 1)
            ->assertJsonPath('data.error', null)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'supplier',
                    'external_import_id',
                    'sent_at',
                    'status' => ['value', 'label'],
                    'total_offers',
                    'processed_offers',
                    'error',
                    'created_at',
                    'completed_at',
                ],
            ]);
    }

    #[Test]
    public function show_returns_404_for_missing_import(): void
    {
        $response = $this->getJson('/api/v1/imports/999999');

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', TranslationHelper::get('translations.common.not_found'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'supplier' => 'supplier-a',
            'external_import_id' => 'imp-001',
            'sent_at' => '2026-09-03T10:00:00Z',
            'offers' => [
                [
                    'external_id' => 'OFF-1',
                    'property' => [
                        'code' => 'BCN-0001',
                        'name' => 'Barcelona Apartment',
                        'city' => 'Barcelona',
                    ],
                    'check_in' => '2026-09-10',
                    'check_out' => '2026-09-15',
                    'max_guests' => 2,
                    'price' => 72500,
                    'currency' => 'EUR',
                    'available_units' => 2,
                    'expires_at' => '2026-09-10T23:59:59Z',
                ],
            ],
        ];
    }
}
