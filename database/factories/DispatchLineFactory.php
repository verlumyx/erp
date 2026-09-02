<?php

namespace Database\Factories;

use App\Modules\Dispatch\Models\Dispatch;
use App\Modules\Item\Models\Item;
use App\Modules\MeasurementUnit\Models\MeasurementUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Dispatch\Models\DispatchLine>
 */
class DispatchLineFactory extends Factory
{
    protected $model = \App\Modules\Dispatch\Models\DispatchLine::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = 1;
        $unitPrice = 100;

        return [
            'company_id' => null,
            'dispatch_id' => Dispatch::factory(),
            'line_number' => 1,
            'item_id' => Item::factory(),
            'measurement_unit_id' => MeasurementUnit::factory(),
            'sourceable_type' => null,
            'sourceable_id' => null,
            'location_id' => null,
            'quantity' => $quantity,
            'base_quantity' => $quantity,
            'unit_price' => $unitPrice,
            'discount_percent' => 0,
            'discount_amount' => 0,
            'tax_id' => null,
            'tax_percent' => 0,
            'tax_amount' => 0,
            'withholding_percent' => 0,
            'withholding_amount' => 0,
            'subtotal' => $quantity * $unitPrice,
            'total' => $quantity * $unitPrice,
            'delivered_quantity' => 0,
            'returned_quantity' => 0,
            'unit_cost' => 60,
            'status' => 'active',
            'notes' => null,
        ];
    }
}
