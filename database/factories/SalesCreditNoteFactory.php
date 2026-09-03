<?php

namespace Database\Factories;

use App\Modules\Client\Models\Client;
use App\Modules\Company\Models\Company;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\SalesCreditNote\Models\SalesCreditNote>
 */
class SalesCreditNoteFactory extends Factory
{
    protected $model = \App\Modules\SalesCreditNote\Models\SalesCreditNote::class;

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
            'code' => 'NCC'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT),
            'client_id' => Client::factory(),
            'sales_invoice_id' => null,
            'sales_return_id' => null,
            'note_series' => null,
            /** El correlativo fiscal se quema al confirmar, no antes. */
            'note_number' => null,
            'note_date' => now()->toDateString(),
            'reason' => 'return',
            'reason_detail' => null,
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
            'fiscal_status' => null,
            'fiscal_uuid' => null,
            'cancelled_at' => null,
            'notes' => null,
            'status' => 'draft',
            'created_by' => User::factory(),
        ];
    }

    /**
     * A confirmed note: it already lowered the client balance and burnt its
     * fiscal number, so it can no longer be edited.
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'confirmed',
            'note_number' => str_pad((string) ++self::$sequence, 8, '0', STR_PAD_LEFT),
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
