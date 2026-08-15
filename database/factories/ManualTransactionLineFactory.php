<?php

namespace Database\Factories;

use App\Modules\ManualTransaction\Models\ManualTransaction;
use App\Modules\ManualTransaction\Models\ManualTransactionLine;
use App\Modules\Transaction\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\ManualTransaction\Models\ManualTransactionLine>
 */
class ManualTransactionLineFactory extends Factory
{
    protected $model = ManualTransactionLine::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $category = fake()->randomElement(array_merge(
            Transaction::INCOME_CATEGORIES,
            Transaction::EXPENSE_CATEGORIES,
        ));

        return [
            'manual_transaction_id' => ManualTransaction::factory(),
            'type' => Transaction::typeForCategory($category),
            'category' => $category,
            'amount' => fake()->randomFloat(2, 1, 500),
            'description' => fake()->optional()->sentence(4),
        ];
    }
}
