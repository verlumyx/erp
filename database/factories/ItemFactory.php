<?php

namespace Database\Factories;

use App\Modules\Company\Models\Company;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Item\Models\Item>
 */
class ItemFactory extends Factory
{
    protected $model = \App\Modules\Item\Models\Item::class;

    private static int $sequence = 0;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sequence = ++self::$sequence;

        return [
            'company_id' => Company::factory(),
            'code' => 'ART'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT),
            'sku' => 'SKU-'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT),
            'barcode' => null,
            'name' => ucfirst(fake()->unique()->words(2, true)),
            'description' => fake()->optional()->sentence(),
            'type' => 'inventoried',
            'category_id' => null,
            'cost_method' => 'average',
            'standard_cost' => 0,
            'average_cost' => 0,
            'min_price' => 0,
            'is_purchasable' => 'yes',
            'is_sellable' => 'yes',
            'min_stock' => 0,
            'max_stock' => 0,
            'reorder_quantity' => 0,
            'weight' => 0,
            'volume' => 0,
            'notes' => null,
            'status' => 'active',
            'created_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the item is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'inactive',
        ]);
    }

    /**
     * Indicate that the item is a service (no stock, no kardex).
     */
    public function service(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'service',
        ]);
    }
}
