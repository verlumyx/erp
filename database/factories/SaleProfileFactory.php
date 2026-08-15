<?php

namespace Database\Factories;

use App\Modules\Account\Models\Profile;
use App\Modules\Sale\Models\Sale;
use App\Modules\Sale\Models\SaleProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Sale\Models\SaleProfile>
 */
class SaleProfileFactory extends Factory
{
    protected $model = SaleProfile::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sale_id' => Sale::factory(),
            'profile_id' => Profile::factory(),
        ];
    }

    public function forSale(Sale $sale): static
    {
        return $this->state(fn (array $attributes): array => [
            'sale_id' => $sale->id,
        ]);
    }
}
