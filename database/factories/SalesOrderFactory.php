<?php

namespace Database\Factories;

use App\Modules\Client\Models\Client;
use App\Modules\Company\Models\Company;
use App\Modules\User\Models\User;
use App\Modules\Warehouse\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\SalesOrder\Models\SalesOrder>
 */
class SalesOrderFactory extends Factory
{
    protected $model = \App\Modules\SalesOrder\Models\SalesOrder::class;

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
            'code' => 'OVE'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT),
            'client_id' => Client::factory(),
            'client_address_id' => null,
            'warehouse_id' => Warehouse::factory(),
            'price_list_id' => null,
            'salesperson_id' => null,
            'route_id' => null,
            'order_date' => now()->toDateString(),
            'expected_date' => null,
            'client_reference' => null,
            'currency' => 'USD',
            'exchange_rate' => 1,
            'payment_term_days' => 0,
            'subtotal' => 0,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total' => 0,
            'dispatched_percent' => 0,
            'invoiced_percent' => 0,
            'approved_by' => null,
            'approved_at' => null,
            'cancelled_at' => null,
            'cancellation_reason' => null,
            'notes' => null,
            'status' => 'draft',
            'created_by' => User::factory(),
        ];
    }

    /**
     * A confirmed order: it already reserves stock, so it can no longer be edited.
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'confirmed',
            'approved_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => 'El cliente desistió del pedido.',
        ]);
    }
}
