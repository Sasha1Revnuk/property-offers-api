<?php

namespace Database\Factories;

use App\Models\Import;
use App\Models\Supplier;
use App\Services\Import\Enumerators\ImportStatusEnumerator;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Import>
 */
class ImportFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'supplier_id' => Supplier::factory(),
            'external_import_id' => fake()->unique()->uuid(),
            'sent_at' => fake()->dateTimeBetween('-1 week', 'now'),
            'status' => ImportStatusEnumerator::Pending,
            'total_offers' => 0,
            'processed_offers' => 0,
            'payload' => null,
            'error' => null,
            'completed_at' => null,
        ];
    }
}
