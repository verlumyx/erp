<?php

namespace Database\Factories;

use App\Modules\Client\Models\Client;
use App\Modules\Company\Models\Company;
use App\Modules\User\Models\User;
use App\Modules\Warehouse\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\SalesReturn\Models\SalesReturn>
 */
class SalesReturnFactory extends Factory
{
    protected $model = \App\Modules\SalesReturn\Models\SalesReturn::class;

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
            'code' => 'DVV'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT),
            'client_id' => Client::factory(),
            'sales_invoice_id' => null,
            'dispatch_id' => null,
            'warehouse_id' => Warehouse::factory(),
            'return_date' => now()->toDateString(),
            'reason' => 'damaged',
            'reason_detail' => null,
            'condition' => 'resalable',
            'currency' => 'USD',
            'exchange_rate' => 1,
            'base_currency' => 'USD',
            'base_exchange_rate' => 1,
            'subtotal' => 0,
            'tax_amount' => 0,
            'total' => 0,
            'credit_note_id' => null,
            'received_by' => null,
            'cancelled_at' => null,
            'notes' => null,
            'status' => 'draft',
            'created_by' => User::factory(),
        ];
    }

    /**
     * A confirmed return: the goods already re-entered the warehouse, so it can
     * no longer be edited.
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
