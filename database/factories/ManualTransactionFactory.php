<?php

namespace Database\Factories;

use App\Modules\Company\Models\Company;
use App\Modules\ManualTransaction\Models\ManualTransaction;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\ManualTransaction\Models\ManualTransaction>
 */
class ManualTransactionFactory extends Factory
{
    protected $model = ManualTransaction::class;

    private static int $sequence = 0;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $code = 'MTX'.str_pad((string) (++self::$sequence), 6, '0', STR_PAD_LEFT);

        return [
            'company_id' => Company::factory(),
            'code' => $code,
            'date' => fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
            'payment_method' => fake()->randomElement(['cash', 'bank_transfer', 'card', 'paypal', 'zelle']),
            'reference' => fake()->optional()->bothify('REF-####'),
            'currency' => 'USD',
            'description' => fake()->optional()->sentence(4),
            'notes' => fake()->optional()->sentence(),
            'recorded_by' => User::factory(),
            'total' => 0,
            'status' => ManualTransaction::STATUS_PENDING,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ManualTransaction::STATUS_APPROVED,
            'approved_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ManualTransaction::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ]);
    }
}
