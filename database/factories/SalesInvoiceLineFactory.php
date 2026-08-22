<?php

namespace Database\Factories;

use App\Modules\Item\Models\Item;
use App\Modules\MeasurementUnit\Models\MeasurementUnit;
use App\Modules\SalesInvoice\Models\SalesInvoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\SalesInvoice\Models\SalesInvoiceLine>
 */
class SalesInvoiceLineFactory extends Factory
{
    protected $model = \App\Modules\SalesInvoice\Models\SalesInvoiceLine::class;

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
            'sales_invoice_id' => SalesInvoice::factory(),
            'line_number' => 1,
            'sourceable_type' => null,
            'sourceable_id' => null,
            'item_id' => Item::factory(),
            'measurement_unit_id' => MeasurementUnit::factory(),
            'warehouse_id' => null,
            'lot_id' => null,
            'serial_id' => null,
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
            'unit_cost' => 0,
            'total_cost' => 0,
            'margin_amount' => 0,
            'returned_quantity' => 0,
            'status' => 'active',
            'notes' => null,
        ];
    }
}
