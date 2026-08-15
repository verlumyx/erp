<?php

namespace Database\Factories;

use App\Modules\Company\Models\Company;
use App\Modules\Service\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Account\Models\Account>
 */
class AccountFactory extends Factory
{
    protected $model = \App\Modules\Account\Models\Account::class;

    private static int $sequence = 0;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $code = 'ACC'.str_pad((string) (++self::$sequence), 6, '0', STR_PAD_LEFT);

        return [
            'company_id' => Company::factory(),
            'code' => $code,
            'service_id' => Service::factory(),
            'email' => fake()->unique()->safeEmail(),
            'password_encrypted' => 'super-secret-password',
            'cost' => fake()->randomFloat(2, 1, 100),
            'purchase_date' => now()->toDateString(),
            'next_renewal' => now()->addDays(30)->toDateString(),
            'status' => 'active',
            'notes' => null,
        ];
    }

    /**
     * Account asociada a un service (y su compañía) concretos.
     */
    public function forService(Service $service): static
    {
        return $this->state(fn (array $attributes): array => [
            'company_id' => $service->company_id,
            'service_id' => $service->id,
        ]);
    }

    public function down(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'down',
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'cancelled',
        ]);
    }
}
