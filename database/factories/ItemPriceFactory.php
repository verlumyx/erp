<?php

namespace Database\Factories;

use App\Modules\Item\Models\Item;
use App\Modules\PriceList\Models\PriceList;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Item\Models\ItemPrice>
 */
class ItemPriceFactory extends Factory
{
    protected $model = \App\Modules\Item\Models\ItemPrice::class;

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
            'price_list_id' => PriceList::factory(),
            'price' => fake()->randomFloat(2, 1, 500),
            'currency' => 'USD',
            'status' => 'active',
        ];
    }
}
