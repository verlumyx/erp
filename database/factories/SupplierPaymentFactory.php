<?php

namespace Database\Factories;

use App\Modules\Company\Models\Company;
use App\Modules\Supplier\Models\Supplier;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\SupplierPayment\Models\SupplierPayment>
 */
class SupplierPaymentFactory extends Factory
{
    protected $model = \App\Modules\SupplierPayment\Models\SupplierPayment::class;

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
            'code' => 'PGP'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT),
            'supplier_id' => Supplier::factory(),
            'origin_type' => 'supplier',
            'origin_id' => null,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'transfer',
            'reference' => null,
            'bank_account' => null,
            'currency' => 'USD',
            'exchange_rate' => 1,
            'base_currency' => 'USD',
            'base_exchange_rate' => 1,
            'amount' => 0,
            'withholding_amount' => 0,
            'applied_amount' => 0,
            'unapplied_amount' => 0,
            'amount_ves' => 0,
            'cancelled_at' => null,
            'cancellation_reason' => null,
            'notes' => null,
            'status' => 'draft',
            'created_by' => User::factory(),
        ];
    }

    /** Un pago que ya movió el saldo de las facturas y del proveedor. */
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
            'cancellation_reason' => 'El pago se registró dos veces.',
        ]);
    }
}
