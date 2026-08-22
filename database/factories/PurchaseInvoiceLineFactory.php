<?php

namespace Database\Factories;

use App\Modules\Item\Models\Item;
use App\Modules\MeasurementUnit\Models\MeasurementUnit;
use App\Modules\PurchaseInvoice\Models\PurchaseInvoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\PurchaseInvoice\Models\PurchaseInvoiceLine>
 */
class PurchaseInvoiceLineFactory extends Factory
{
    protected $model = \App\Modules\PurchaseInvoice\Models\PurchaseInvoiceLine::class;

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
            'purchase_invoice_id' => PurchaseInvoice::factory(),
            'line_number' => 1,
            'item_id' => Item::factory(),
            'measurement_unit_id' => MeasurementUnit::factory(),
            'sourceable_type' => null,
            'sourceable_id' => null,
            'warehouse_id' => null,
            'lot_id' => null,
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
            'landed_cost' => $unitPrice,
            'returned_quantity' => 0,
            'status' => 'active',
            'notes' => null,
        ];
    }
}
