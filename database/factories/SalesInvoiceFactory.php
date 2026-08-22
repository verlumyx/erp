<?php

namespace Database\Factories;

use App\Modules\Client\Models\Client;
use App\Modules\Company\Models\Company;
use App\Modules\User\Models\User;
use App\Modules\Warehouse\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\SalesInvoice\Models\SalesInvoice>
 */
class SalesInvoiceFactory extends Factory
{
    protected $model = \App\Modules\SalesInvoice\Models\SalesInvoice::class;

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
            'code' => 'FVE'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT),
            'client_id' => Client::factory(),
            'sourceable_type' => null,
            'sourceable_id' => null,
            'dispatch_id' => null,
            'client_address_id' => null,
            'warehouse_id' => Warehouse::factory(),
            'salesperson_id' => null,
            'invoice_series' => null,
            /** El correlativo fiscal se quema al confirmar, no antes. */
            'invoice_number' => null,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->toDateString(),
            'sale_type' => 'credit',
            'currency' => 'USD',
            'exchange_rate' => 1,
            'base_currency' => 'USD',
            'base_exchange_rate' => 1,
            'affects_inventory' => 'yes',
            'subtotal' => 0,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'withholding_amount' => 0,
            'freight_amount' => 0,
            'total' => 0,
            'total_cost' => 0,
            'subtotal_ves' => 0,
            'tax_amount_ves' => 0,
            'total_ves' => 0,
            'paid_amount' => 0,
            'balance' => 0,
            'payment_status' => 'pending',
            'fiscal_status' => null,
            'fiscal_uuid' => null,
            'printed_at' => null,
            'cancelled_at' => null,
            'cancellation_reason' => null,
            'notes' => null,
            'status' => 'draft',
            'created_by' => User::factory(),
        ];
    }

    /**
     * An issued invoice: it already burned its fiscal number and posted the
     * receivable, so it can no longer be edited.
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'confirmed',
            'invoice_number' => str_pad((string) self::$sequence, 8, '0', STR_PAD_LEFT),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => 'La factura se emitió con datos equivocados.',
        ]);
    }
}
