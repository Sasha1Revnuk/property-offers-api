<?php

namespace Database\Factories;

use App\Models\Import;
use App\Models\Offer;
use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Offer>
 */
class OfferFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $checkIn = fake()->dateTimeBetween('+1 day', '+30 days');
        $checkOut = (clone $checkIn)->modify('+'.fake()->numberBetween(2, 14).' days');

        return [
            'import_id' => Import::factory(),
            'supplier_id' => function (array $attributes): int {
                $importId = $attributes['import_id'];

                if (! is_int($importId) && ! is_string($importId)) {
                    throw new \InvalidArgumentException('Offer factory requires a resolved import_id.');
                }

                $supplierId = Import::query()->whereKey($importId)->value('supplier_id');

                if (! is_numeric($supplierId)) {
                    throw new \RuntimeException('Unable to resolve supplier_id for import.');
                }

                return (int) $supplierId;
            },
            'property_id' => Property::factory(),
            'external_id' => fake()->unique()->uuid(),
            'check_in' => $checkIn->format('Y-m-d'),
            'check_out' => $checkOut->format('Y-m-d'),
            'max_guests' => fake()->numberBetween(1, 8),
            'price' => fake()->numberBetween(5_000, 200_000),
            'currency' => 'EUR',
            'available_units' => fake()->numberBetween(1, 10),
            'expires_at' => fake()->dateTimeBetween('+1 day', '+60 days'),
        ];
    }
}
