<?php

namespace Database\Factories;

use App\Modules\Sale\Models\Sale;
use App\Modules\Sale\Models\SaleRenewal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Sale\Models\SaleRenewal>
 */
class SaleRenewalFactory extends Factory
{
    protected $model = SaleRenewal::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $durationDays = fake()->randomElement([30, 60, 90]);
        $previousEnd = now()->subDays(fake()->numberBetween(1, 20));
        $newEnd = $previousEnd->copy()->addDays($durationDays);

        return [
            'sale_id' => Sale::factory(),
            'renewed_at' => now()->toDateString(),
            'previous_end_date' => $previousEnd->toDateString(),
            'new_end_date' => $newEnd->toDateString(),
            'duration_days' => $durationDays,
            'price' => fake()->randomFloat(2, 1, 200),
            'renewed_by' => null,
            'notes' => null,
        ];
    }

    public function forSale(Sale $sale): static
    {
        return $this->state(fn (array $attributes): array => [
            'sale_id' => $sale->id,
        ]);
    }
}
