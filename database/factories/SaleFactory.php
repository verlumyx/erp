<?php

namespace Database\Factories;

use App\Modules\Client\Models\Client;
use App\Modules\Company\Models\Company;
use App\Modules\Plan\Models\Plan;
use App\Modules\Sale\Models\Sale;
use App\Modules\Service\Models\Service;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Sale\Models\Sale>
 */
class SaleFactory extends Factory
{
    protected $model = Sale::class;

    private static int $sequence = 0;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $code = 'SAL'.str_pad((string) (++self::$sequence), 6, '0', STR_PAD_LEFT);

        $durationDays = fake()->randomElement([30, 60, 90]);
        $start = now()->subDays(fake()->numberBetween(0, 10));

        return [
            'company_id' => Company::factory(),
            'code' => $code,
            'client_id' => Client::factory(),
            'plan_id' => Plan::factory(),
            'agent_id' => User::factory(),
            'service_id' => Service::factory(),
            'capacity' => 'profile',
            'duration_days' => $durationDays,
            'price' => fake()->randomFloat(2, 1, 200),
            'start_date' => $start->toDateString(),
            'end_date' => $start->copy()->addDays($durationDays)->toDateString(),
            'status' => Sale::STATUS_ACTIVE,
            'notes' => null,
        ];
    }

    /**
     * Venta vigente que vence en el futuro.
     */
    public function active(): static
    {
        return $this->state(function (array $attributes): array {
            $start = now()->subDays(5);

            return [
                'status' => Sale::STATUS_ACTIVE,
                'start_date' => $start->toDateString(),
                'end_date' => $start->copy()->addDays((int) $attributes['duration_days'])->toDateString(),
            ];
        });
    }

    /**
     * Venta expirada dentro del periodo de gracia (renovable).
     */
    public function expiredInGrace(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => Sale::STATUS_EXPIRED,
            'end_date' => now()->subDay()->toDateString(),
        ]);
    }

    /**
     * Venta expirada fuera del periodo de gracia (solo reactivable).
     */
    public function expiredOutOfGrace(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => Sale::STATUS_EXPIRED,
            'end_date' => now()->subDays(15)->toDateString(),
        ]);
    }

    /**
     * Venta expulsada (cancelada).
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => Sale::STATUS_CANCELLED,
            'cancelled_at' => now()->subDays(2),
            'cancellation_reason' => 'Cancelada por falta de pago.',
        ]);
    }

    /**
     * Venta de cuenta completa (capacity = full_account).
     */
    public function fullAccount(): static
    {
        return $this->state(fn (array $attributes): array => [
            'capacity' => 'full_account',
        ]);
    }

    /**
     * Venta asociada a un plan/servicio/compañía concretos (snapshot del plan).
     */
    public function forPlan(Plan $plan): static
    {
        return $this->state(fn (array $attributes): array => [
            'company_id' => $plan->company_id,
            'plan_id' => $plan->id,
            'service_id' => $plan->service_id,
            'capacity' => $plan->capacity,
            'duration_days' => $plan->duration_days,
            'price' => $plan->sale_price,
        ]);
    }
}
