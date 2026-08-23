<?php

namespace Database\Factories;

use App\Modules\Company\Models\Company;
use App\Modules\Supplier\Models\Supplier;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\SupplierAdvance\Models\SupplierAdvance>
 */
class SupplierAdvanceFactory extends Factory
{
    protected $model = \App\Modules\SupplierAdvance\Models\SupplierAdvance::class;

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
            'code' => 'ANP'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT),
            'supplier_id' => Supplier::factory(),
            'purchase_order_id' => null,
            'advance_date' => now()->toDateString(),
            'payment_method' => 'transfer',
            'reference' => null,
            'bank_account' => null,
            'currency' => 'USD',
            'exchange_rate' => 1,
            'base_currency' => 'USD',
            'base_exchange_rate' => 1,
            'amount' => 0,
            'applied_amount' => 0,
            'balance' => 0,
            'cancelled_at' => null,
            'notes' => null,
            'status' => 'draft',
            'created_by' => User::factory(),
        ];
    }

    /** Aprobado: ya tiene pago espejo, pero todavía no entregó nada. */
    public function pendingConfirmation(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'pending_confirmation',
        ]);
    }

    /** Entregado: su pago se confirmó y suma al crédito del proveedor. */
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
