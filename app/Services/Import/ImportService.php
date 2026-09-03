<?php

namespace App\Services\Import;

use App\Jobs\ProcessImportJob;
use App\Models\Import;
use App\Models\Offer;
use App\Models\Property;
use App\Models\Supplier;
use App\Services\Import\Contracts\ImportServiceInterface;
use App\Services\Import\Dto\CreateImportDto;
use App\Services\Import\Dto\ImportOfferDto;
use App\Services\Import\Enumerators\ImportStatusEnumerator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use JsonException;
use RuntimeException;
use Throwable;

class ImportService implements ImportServiceInterface
{
    private const int CHUNK_SIZE = 1000;

    public function createImport(CreateImportDto $dto): Import
    {
        $supplier = Supplier::query()->where('slug', $dto->supplier)->firstOrFail();

        try {
            $import = Import::query()->firstOrCreate(
                [
                    'supplier_id' => $supplier->id,
                    'external_import_id' => $dto->externalImportId,
                ],
                [
                    'sent_at' => $dto->sentAt,
                    'status' => ImportStatusEnumerator::Pending,
                    'total_offers' => count($dto->offers),
                    'processed_offers' => 0,
                    'payload' => json_encode($dto->offersToArray(), JSON_THROW_ON_ERROR),
                ],
            );

            if ($import->wasRecentlyCreated) {
                ProcessImportJob::dispatch($import->id);
            }

            return $import;
        } catch (QueryException) {
            return Import::query()
                ->where('supplier_id', $supplier->id)
                ->where('external_import_id', $dto->externalImportId)
                ->firstOrFail();
        }
    }

    public function processImport(int $importId): void
    {
        $import = Import::query()->findOrFail($importId);

        $import->update([
            'status' => ImportStatusEnumerator::Processing,
        ]);

        try {
            $offers = $this->decodePayload($import->payload);
            $now = Carbon::now()->toDateTimeString();

            $propertiesByCode = [];
            foreach ($offers as $offer) {
                $propertiesByCode[$offer->propertyCode] = [
                    'code' => $offer->propertyCode,
                    'name' => $offer->propertyName,
                    'city' => $offer->propertyCity,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach (array_chunk(array_values($propertiesByCode), self::CHUNK_SIZE) as $chunk) {
                Property::query()->upsert(
                    $chunk,
                    uniqueBy: ['code'],
                    update: ['name', 'city', 'updated_at'],
                );
            }

            /** @var array<string, int> $propertyIdByCode */
            $propertyIdByCode = Property::query()
                ->whereIn('code', array_keys($propertiesByCode))
                ->pluck('id', 'code')
                ->all();

            foreach (array_chunk($offers, self::CHUNK_SIZE) as $chunk) {
                DB::transaction(function () use ($chunk, $import, $propertyIdByCode, $now): void {
                    $rows = [];

                    foreach ($chunk as $offer) {
                        $propertyId = $propertyIdByCode[$offer->propertyCode] ?? null;

                        if ($propertyId === null) {
                            throw new RuntimeException(
                                "Property code [{$offer->propertyCode}] was not found after upsert.",
                            );
                        }

                        $rows[] = [
                            'supplier_id' => $import->supplier_id,
                            'external_id' => $offer->externalId,
                            'property_id' => $propertyId,
                            'import_id' => $import->id,
                            'check_in' => $offer->checkIn,
                            'check_out' => $offer->checkOut,
                            'max_guests' => $offer->maxGuests,
                            'price' => $offer->price,
                            'currency' => $offer->currency,
                            'available_units' => $offer->availableUnits,
                            'expires_at' => Carbon::parse($offer->expiresAt)->toDateTimeString(),
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }

                    Offer::query()->upsert(
                        $rows,
                        uniqueBy: ['supplier_id', 'external_id'],
                        update: [
                            'property_id',
                            'import_id',
                            'check_in',
                            'check_out',
                            'max_guests',
                            'price',
                            'currency',
                            'available_units',
                            'expires_at',
                            'updated_at',
                        ],
                    );

                    $import->increment('processed_offers', count($rows));
                });
            }

            $import->update([
                'status' => ImportStatusEnumerator::Completed,
                'completed_at' => Carbon::now(),
                'payload' => null,
            ]);
        } catch (Throwable $e) {
            $import->update([
                'status' => ImportStatusEnumerator::Failed,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * @return list<ImportOfferDto>
     */
    private function decodePayload(?string $payload): array
    {
        if ($payload === null || $payload === '') {
            throw new RuntimeException('Import payload is empty.');
        }

        try {
            /** @var mixed $decoded */
            $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new RuntimeException('Import payload is not valid JSON.', 0, $e);
        }

        if (! is_array($decoded)) {
            throw new RuntimeException('Import payload must be a JSON array.');
        }

        $offers = [];

        foreach ($decoded as $item) {
            if (! is_array($item)) {
                throw new RuntimeException('Import payload contains an invalid offer entry.');
            }

            /** @var array{
             *     external_id: string,
             *     property: array{code: string, name: string, city: string},
             *     check_in: string,
             *     check_out: string,
             *     max_guests: int,
             *     price: int,
             *     currency: string,
             *     available_units: int,
             *     expires_at: string
             * } $item
             */
            $offers[] = ImportOfferDto::fromArray($item);
        }

        return $offers;
    }
}
