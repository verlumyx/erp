<?php

namespace Database\Factories;

use App\Modules\Company\Models\Company;
use App\Modules\Supplier\Models\Supplier;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\PurchaseCreditNote\Models\PurchaseCreditNote>
 */
class PurchaseCreditNoteFactory extends Factory
{
    protected $model = \App\Modules\PurchaseCreditNote\Models\PurchaseCreditNote::class;

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
            'code' => 'NCP'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT),
            'supplier_id' => Supplier::factory(),
            'purchase_invoice_id' => null,
            'purchase_return_id' => null,
            'supplier_document_number' => null,
            'note_date' => now()->toDateString(),
            'reason' => 'return',
            'reason_detail' => null,
            'affects_inventory' => 'no',
            'currency' => 'USD',
            'exchange_rate' => 1,
            'base_currency' => 'USD',
            'base_exchange_rate' => 1,
            'subtotal' => 0,
            'tax_amount' => 0,
            'total' => 0,
            'subtotal_ves' => 0,
            'tax_amount_ves' => 0,
            'total_ves' => 0,
            'applied_amount' => 0,
            'balance' => 0,
            'cancelled_at' => null,
            'notes' => null,
            'status' => 'draft',
            'created_by' => User::factory(),
        ];
    }

    /**
     * A confirmed note: it already lowered the supplier balance, so it can no
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
        ]);
    }
}
