<?php

namespace Database\Factories;

use App\Modules\Company\Models\Company;
use App\Modules\Supplier\Models\Supplier;
use App\Modules\User\Models\User;
use App\Modules\Warehouse\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\PurchaseInvoice\Models\PurchaseInvoice>
 */
class PurchaseInvoiceFactory extends Factory
{
    protected $model = \App\Modules\PurchaseInvoice\Models\PurchaseInvoice::class;

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
            'code' => 'FCO'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT),
            'supplier_id' => Supplier::factory(),
            'sourceable_type' => null,
            'sourceable_id' => null,
            'entry_id' => null,
            'warehouse_id' => Warehouse::factory(),
            'supplier_invoice_number' => '00-'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT),
            'supplier_invoice_series' => null,
            'invoice_date' => now()->toDateString(),
            'received_date' => null,
            'due_date' => now()->toDateString(),
            'currency' => 'USD',
            'exchange_rate' => 1,
            'base_currency' => 'USD',
            'base_exchange_rate' => 1,
            'subtotal' => 0,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'withholding_amount' => 0,
            'total' => 0,
            'subtotal_ves' => 0,
            'tax_amount_ves' => 0,
            'total_ves' => 0,
            'paid_amount' => 0,
            'balance' => 0,
            'payment_status' => 'pending',
            'cancelled_at' => null,
            'cancellation_reason' => null,
            'notes' => null,
            'status' => 'draft',
            'created_by' => User::factory(),
        ];
    }

    /**
     * A confirmed invoice: it already generated the payable, so it can no
     * longer be edited.
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'confirmed',
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => 'La factura llegó con el número equivocado.',
        ]);
    }
}
