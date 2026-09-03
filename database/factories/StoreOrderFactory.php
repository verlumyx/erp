<?php

namespace Database\Factories;

use App\Modules\Company\Models\Company;
use App\Modules\Store\Models\StoreCustomer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Store\Models\StoreOrder>
 */
class StoreOrderFactory extends Factory
{
    protected $model = \App\Modules\Store\Models\StoreOrder::class;

    private static int $sequence = 0;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sequence = ++self::$sequence;

        return [
            'company_id' => Company::factory(),
            'code' => 'PWE'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT),
            'store_customer_id' => fn (array $attributes): string => StoreCustomer::factory()->create([
                'company_id' => $attributes['company_id'],
            ])->id,
            'client_id' => null,
            'client_address_id' => null,
            'sales_order_id' => null,
            'buyer_name' => fake()->name(),
            'buyer_document_type' => null,
            'buyer_document_number' => null,
            'buyer_email' => fake()->safeEmail(),
            'buyer_phone' => fake()->numerify('+58 4## ### ####'),
            'delivery_address' => fake()->streetAddress(),
            'delivery_city' => fake()->city(),
            'delivery_state' => null,
            'currency' => 'USD',
            'exchange_rate' => 36.5,
            'subtotal' => 0,
            'total' => 0,
            'buyer_notes' => null,
            'converted_by' => null,
            'converted_at' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
            'notes' => null,
            'status' => 'pending',
            'created_by' => null,
        ];
    }

    public function converted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'converted',
            'converted_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'rejected',
            'rejected_at' => now(),
            'rejection_reason' => 'Rechazado en prueba',
        ]);
    }
}
