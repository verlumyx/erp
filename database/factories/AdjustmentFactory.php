<?php

namespace Database\Factories;

use App\Modules\Company\Models\Company;
use App\Modules\User\Models\User;
use App\Modules\Warehouse\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Adjustment\Models\Adjustment>
 */
class AdjustmentFactory extends Factory
{
    protected $model = \App\Modules\Adjustment\Models\Adjustment::class;

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
            'code' => 'AJU'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT),
            'warehouse_id' => Warehouse::factory(),
            'adjustment_date' => now()->toDateString(),
            'type' => 'physical_count',
            'direction' => 'mixed',
            'reason' => 'Conteo físico mensual.',
            'count_id' => null,
            'total_quantity_in' => 0,
            'total_quantity_out' => 0,
            'total_cost_in' => 0,
            'total_cost_out' => 0,
            'net_cost' => 0,
            'approved_by' => null,
            'approved_at' => null,
            'cancelled_at' => null,
            'cancellation_reason' => null,
            'attachment_path' => null,
            'notes' => null,
            'status' => 'draft',
            'created_by' => User::factory(),
        ];
    }

    /** Esperando la firma de quien puede aplicarlo. */
    public function pendingApproval(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'pending_approval',
        ]);
    }

    /**
     * A confirmed adjustment: the stock already changed, so it can no longer be
     * edited.
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
            'cancellation_reason' => 'Se contó mal.',
        ]);
    }

    /** Ajuste que solo reexpresa el costo de lo que ya está en la bodega. */
    public function revaluation(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'revaluation',
        ]);
    }
}
