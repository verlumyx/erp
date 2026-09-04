<?php

namespace Database\Factories;

use App\Modules\Company\Models\Company;
use App\Modules\User\Models\User;
use App\Modules\Warehouse\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Import\Models\Import>
 */
class ImportFactory extends Factory
{
    protected $model = \App\Modules\Import\Models\Import::class;

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
            'code' => 'IMP'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT),
            'warehouse_id' => Warehouse::factory(),
            'import_date' => now()->toDateString(),
            'arrival_date' => null,
            'reference' => 'BL-'.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT),
            'allocation_method' => 'value',
            'currency' => 'USD',
            'exchange_rate' => 1,
            'base_currency' => 'USD',
            'base_exchange_rate' => 1,
            'total_charges' => 0,
            'total_base_value' => 0,
            'total_landed_value' => 0,
            'capitalized_amount' => 0,
            'variance_amount' => 0,
            'adjustment_id' => null,
            'cancelled_at' => null,
            'cancellation_reason' => null,
            'notes' => null,
            'status' => 'draft',
            'created_by' => User::factory(),
        ];
    }

    /** El expediente ya generó su ajuste de revaluación. */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'confirmed',
        ]);
    }

    /** El ajuste ya se aplicó: el expediente quedó cerrado. */
    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'completed',
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => 'El embarque llegó partido.',
        ]);
    }
}
