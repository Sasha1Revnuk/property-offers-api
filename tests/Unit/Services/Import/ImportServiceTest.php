<?php

namespace Tests\Unit\Services\Import;

use App\Jobs\ProcessImportJob;
use App\Models\Import;
use App\Models\Offer;
use App\Models\Property;
use App\Models\Supplier;
use App\Services\Import\Contracts\ImportServiceInterface;
use App\Services\Import\Dto\CreateImportDto;
use App\Services\Import\Dto\ImportOfferDto;
use App\Services\Import\Enumerators\ImportStatusEnumerator;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class ImportServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[Test]
    public function create_import_persists_pending_import_with_payload_and_dispatches_job(): void
    {
        Queue::fake();

        $supplier = Supplier::factory()->create(['slug' => 'supplier-a']);
        $dto = $this->makeCreateImportDto(
            supplier: $supplier->slug,
            externalImportId: 'imp-001',
        );

        $import = $this->service()->createImport($dto);

        $this->assertModelExists($import);
        $this->assertSame(ImportStatusEnumerator::Pending, $import->status);
        $this->assertSame(1, $import->total_offers);
        $this->assertSame(0, $import->processed_offers);
        $this->assertNotNull($import->payload);
        $this->assertStringContainsString('OFF-1', (string) $import->payload);

        Queue::assertPushed(ProcessImportJob::class, function (ProcessImportJob $job) use ($import): bool {
            return $job->importId === $import->id;
        });
    }

    #[Test]
    public function create_import_is_idempotent_and_does_not_redispatch_job(): void
    {
        Queue::fake();

        $supplier = Supplier::factory()->create(['slug' => 'supplier-a']);
        $dto = $this->makeCreateImportDto(
            supplier: $supplier->slug,
            externalImportId: 'imp-002',
        );

        $first = $this->service()->createImport($dto);
        $first->update(['status' => ImportStatusEnumerator::Processing]);

        $second = $this->service()->createImport($dto);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(ImportStatusEnumerator::Processing, $second->refresh()->status);
        Queue::assertPushed(ProcessImportJob::class, 1);
    }

    #[Test]
    public function process_import_upserts_properties_and_offers_and_clears_payload(): void
    {
        $supplier = Supplier::factory()->create(['slug' => 'supplier-a']);
        $dto = $this->makeCreateImportDto(
            supplier: $supplier->slug,
            externalImportId: 'imp-003',
            offers: [
                $this->makeOfferDto(
                    externalId: 'OFF-1',
                    propertyCode: 'BCN-0001',
                    price: 72500,
                    availableUnits: 2,
                ),
            ],
        );

        Queue::fake();
        $import = $this->service()->createImport($dto);

        $this->service()->processImport($import->id);

        $import->refresh();
        $this->assertSame(ImportStatusEnumerator::Completed, $import->status);
        $this->assertNull($import->payload);
        $this->assertSame(1, $import->processed_offers);
        $this->assertNotNull($import->completed_at);

        $property = Property::query()->where('code', 'BCN-0001')->first();
        $this->assertNotNull($property);
        $this->assertSame('Barcelona Apartment', $property->name);
        $this->assertSame('Barcelona', $property->city);

        $offer = Offer::query()->where('external_id', 'OFF-1')->first();
        $this->assertNotNull($offer);
        $this->assertSame($import->id, $offer->import_id);
        $this->assertSame(72500, $offer->price);
        $this->assertSame(2, $offer->available_units);
        $this->assertSame(1, Offer::query()->count());
    }

    #[Test]
    public function process_import_updates_existing_offer_from_another_import(): void
    {
        $supplier = Supplier::factory()->create(['slug' => 'supplier-a']);

        Queue::fake();

        $firstImport = $this->service()->createImport($this->makeCreateImportDto(
            supplier: $supplier->slug,
            externalImportId: 'imp-004',
            offers: [
                $this->makeOfferDto(
                    externalId: 'OFF-SHARED',
                    propertyCode: 'BCN-0001',
                    price: 50000,
                    availableUnits: 5,
                ),
            ],
        ));
        $this->service()->processImport($firstImport->id);

        $secondImport = $this->service()->createImport($this->makeCreateImportDto(
            supplier: $supplier->slug,
            externalImportId: 'imp-005',
            offers: [
                $this->makeOfferDto(
                    externalId: 'OFF-SHARED',
                    propertyCode: 'BCN-0001',
                    price: 45000,
                    availableUnits: 3,
                ),
            ],
        ));
        $this->service()->processImport($secondImport->id);

        $this->assertSame(1, Offer::query()->count());

        $offer = Offer::query()->where('external_id', 'OFF-SHARED')->firstOrFail();
        $this->assertSame($secondImport->id, $offer->import_id);
        $this->assertSame(45000, $offer->price);
        $this->assertSame(3, $offer->available_units);
    }

    #[Test]
    public function process_import_marks_failed_when_payload_is_invalid(): void
    {
        $import = Import::factory()->create([
            'payload' => 'not-json',
            'status' => ImportStatusEnumerator::Pending,
        ]);

        try {
            $this->service()->processImport($import->id);
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (RuntimeException) {
            // expected
        }

        $import->refresh();
        $this->assertSame(ImportStatusEnumerator::Failed, $import->status);
        $this->assertNotNull($import->error);
        $this->assertStringContainsString('JSON', $import->error);
    }

    private function service(): ImportServiceInterface
    {
        return $this->app->make(ImportServiceInterface::class);
    }

    /**
     * @param  list<ImportOfferDto>|null  $offers
     */
    private function makeCreateImportDto(
        string $supplier,
        string $externalImportId,
        ?array $offers = null,
    ): CreateImportDto {
        return new CreateImportDto(
            supplier: $supplier,
            externalImportId: $externalImportId,
            sentAt: '2026-09-03T10:00:00Z',
            offers: $offers ?? [$this->makeOfferDto()],
        );
    }

    private function makeOfferDto(
        string $externalId = 'OFF-1',
        string $propertyCode = 'BCN-0001',
        int $price = 72500,
        int $availableUnits = 2,
    ): ImportOfferDto {
        return new ImportOfferDto(
            externalId: $externalId,
            propertyCode: $propertyCode,
            propertyName: 'Barcelona Apartment',
            propertyCity: 'Barcelona',
            checkIn: '2026-09-10',
            checkOut: '2026-09-15',
            maxGuests: 2,
            price: $price,
            currency: 'EUR',
            availableUnits: $availableUnits,
            expiresAt: '2026-09-10T23:59:59Z',
        );
    }
}
