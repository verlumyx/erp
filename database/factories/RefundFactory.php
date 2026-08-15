<?php

namespace Database\Factories;

use App\Modules\Client\Models\Client;
use App\Modules\Company\Models\Company;
use App\Modules\Refund\Models\Refund;
use App\Modules\Sale\Models\Sale;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Refund\Models\Refund>
 */
class RefundFactory extends Factory
{
    protected $model = Refund::class;

    private static int $sequence = 0;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $code = 'REF'.str_pad((string) (++self::$sequence), 6, '0', STR_PAD_LEFT);

        return [
            'company_id' => Company::factory(),
            'code' => $code,
            'sale_id' => Sale::factory(),
            'client_id' => Client::factory(),
            'amount' => fake()->randomFloat(2, 1, 200),
            'reason' => fake()->sentence(),
            'status' => Refund::STATUS_PENDING,
            'requested_by' => User::factory(),
            'resolved_by' => null,
            'resolved_at' => null,
            'notes' => null,
        ];
    }

    /**
     * Reembolso a la espera de aprobación.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => Refund::STATUS_PENDING,
            'resolved_by' => null,
            'resolved_at' => null,
        ]);
    }

    /**
     * Reembolso aprobado (ya generó el egreso contable).
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => Refund::STATUS_APPROVED,
            'resolved_by' => User::factory(),
            'resolved_at' => now()->subDay(),
        ]);
    }

    /**
     * Reembolso rechazado (sin efecto contable).
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => Refund::STATUS_REJECTED,
            'resolved_by' => User::factory(),
            'resolved_at' => now()->subDay(),
        ]);
    }

    /**
     * Reembolso asociado a una venta concreta (snapshot de compañía y cliente).
     */
    public function forSale(Sale $sale): static
    {
        return $this->state(fn (array $attributes): array => [
            'company_id' => $sale->company_id,
            'sale_id' => $sale->id,
            'client_id' => $sale->client_id,
            'amount' => $sale->price,
        ]);
    }
}
