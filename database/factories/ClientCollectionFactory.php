<?php

namespace Database\Factories;

use App\Modules\Client\Models\Client;
use App\Modules\Company\Models\Company;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\ClientCollection\Models\ClientCollection>
 */
class ClientCollectionFactory extends Factory
{
    protected $model = \App\Modules\ClientCollection\Models\ClientCollection::class;

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
            'code' => 'COB'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT),
            'client_id' => Client::factory(),
            'origin_type' => 'client',
            'origin_id' => null,
            'collection_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'reference' => null,
            'bank_account' => null,
            'collected_by' => null,
            'route_id' => null,
            'currency' => 'USD',
            'exchange_rate' => 1,
            'base_currency' => 'USD',
            'base_exchange_rate' => 1,
            'amount' => 0,
            'withholding_amount' => 0,
            'applied_amount' => 0,
            'unapplied_amount' => 0,
            'amount_ves' => 0,
            'check_number' => null,
            'check_date' => null,
            'check_status' => null,
            'cancelled_at' => null,
            'cancellation_reason' => null,
            'notes' => null,
            'status' => 'draft',
            'created_by' => User::factory(),
        ];
    }

    /** Un cobro que ya movió el saldo de las facturas y del cliente. */
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
            'cancellation_reason' => 'El cobro se registró dos veces.',
        ]);
    }

    /** Cobrado con cheque, todavía sin depositar. */
    public function withCheck(string $number = '00012345'): static
    {
        return $this->state(fn (array $attributes): array => [
            'payment_method' => 'check',
            'check_number' => $number,
            'check_date' => now()->toDateString(),
            'check_status' => 'pending',
        ]);
    }
}
