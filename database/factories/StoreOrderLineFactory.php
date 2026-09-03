<?php

namespace Database\Factories;

use App\Modules\Item\Models\Item;
use App\Modules\MeasurementUnit\Models\MeasurementUnit;
use App\Modules\Store\Models\StoreItem;
use App\Modules\Store\Models\StoreOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Store\Models\StoreOrderLine>
 */
class StoreOrderLineFactory extends Factory
{
    protected $model = \App\Modules\Store\Models\StoreOrderLine::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 5);
        $price = fake()->randomFloat(2, 1, 100);

        return [
            'store_order_id' => StoreOrder::factory(),
            'company_id' => fn (array $attributes): ?string => StoreOrder::find($attributes['store_order_id'])?->company_id,
            'line_number' => 1,
            'item_id' => Item::factory(),
            'store_item_id' => fn (array $attributes): string => StoreItem::factory()->create([
                'company_id' => $attributes['company_id'],
                'item_id' => $attributes['item_id'],
            ])->id,
            'measurement_unit_id' => MeasurementUnit::factory(),
            'quantity' => $quantity,
            'base_quantity' => $quantity,
            'unit_price' => $price,
            'list_price' => $price,
            'discount_percent' => 0,
            'discount_amount' => 0,
            'tax_id' => null,
            'tax_percent' => 0,
            'tax_amount' => 0,
            'withholding_percent' => 0,
            'withholding_amount' => 0,
            'subtotal' => round($quantity * $price, 2),
            'total' => round($quantity * $price, 2),
            'status' => 'active',
            'notes' => null,
        ];
    }
}
