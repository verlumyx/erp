<?php

namespace Database\Factories;

use App\Modules\Client\Models\Client;
use App\Modules\Company\Models\Company;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\ClientAdvance\Models\ClientAdvance>
 */
class ClientAdvanceFactory extends Factory
{
    protected $model = \App\Modules\ClientAdvance\Models\ClientAdvance::class;

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
            'code' => 'ANC'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT),
            'client_id' => Client::factory(),
            'sales_order_id' => null,
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
            'refunded_amount' => 0,
            'cancelled_at' => null,
            'notes' => null,
            'status' => 'draft',
            'created_by' => User::factory(),
        ];
    }

    /** Aprobado: ya tiene cobro espejo, pero todavía no recibió nada. */
    public function pendingConfirmation(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'pending_confirmation',
        ]);
    }

    /** Recibido: su cobro se confirmó y suma al crédito del cliente. */
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
