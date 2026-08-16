<?php

namespace Database\Factories;

use App\Modules\Item\Models\Item;
use App\Modules\MeasurementUnit\Models\MeasurementUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Item\Models\ItemUnit>
 */
class ItemUnitFactory extends Factory
{
    protected $model = \App\Modules\Item\Models\ItemUnit::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => null,
            'item_id' => Item::factory(),
            'measurement_unit_id' => MeasurementUnit::factory(),
            'is_base' => 'no',
            'conversion_factor' => 1,
            'status' => 'active',
        ];
    }

    /**
     * The base unit of the item: exactly one per item, factor always 1.
     */
    public function base(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_base' => 'yes',
            'conversion_factor' => 1,
        ]);
    }
}
