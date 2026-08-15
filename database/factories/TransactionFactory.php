<?php

namespace Database\Factories;

use App\Modules\Account\Models\Account;
use App\Modules\Company\Models\Company;
use App\Modules\Transaction\Models\Transaction;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Transaction\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    /**
     * Por defecto genera un gasto coherente (type/category) sin relación
     * polimórfica. Usa los estados income()/expense()/forAccount() para casos
     * específicos.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Categorías de gasto que no exigen una relación polimórfica.
        $category = fake()->randomElement(array_values(array_filter(
            Transaction::EXPENSE_CATEGORIES,
            static fn (string $cat): bool => $cat !== 'streaming_account',
        )));

        return [
            'company_id' => Company::factory(),
            'type' => Transaction::TYPE_EXPENSE,
            'category' => $category,
            'subcategory' => null,
            'related_type' => null,
            'related_id' => null,
            'amount' => fake()->randomFloat(2, 1, 500),
            'currency' => 'USD',
            'date' => fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
            'payment_method' => fake()->randomElement(['cash', 'bank_transfer', 'card', 'paypal', 'zelle']),
            'reference' => fake()->optional()->bothify('REF-####'),
            'period_from' => null,
            'period_to' => null,
            'description' => fake()->sentence(4),
            'notes' => fake()->optional()->sentence(),
            'recorded_by' => User::factory(),
            'receipt_url' => null,
        ];
    }

    /**
     * Ingreso con una categoría de income válida.
     */
    public function income(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => Transaction::TYPE_INCOME,
            'category' => fake()->randomElement(Transaction::INCOME_CATEGORIES),
        ]);
    }

    /**
     * Gasto con una categoría de expense válida (sin streaming_account).
     */
    public function expense(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => Transaction::TYPE_EXPENSE,
            'category' => fake()->randomElement(array_values(array_filter(
                Transaction::EXPENSE_CATEGORIES,
                static fn (string $cat): bool => $cat !== 'streaming_account',
            ))),
        ]);
    }

    /**
     * Pago de una cuenta de streaming: gasto polimórfico ligado a un Account.
     */
    public function forAccount(Account $account): static
    {
        return $this->state(fn (array $attributes): array => [
            'company_id' => $account->company_id,
            'type' => Transaction::TYPE_EXPENSE,
            'category' => 'streaming_account',
            'related_type' => 'Account',
            'related_id' => $account->id,
        ]);
    }
}
