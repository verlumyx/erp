<?php

namespace Database\Factories;

use App\Modules\ClientCollection\Models\ClientCollection;
use App\Modules\Company\Models\Company;
use App\Modules\SalesInvoice\Models\SalesInvoice;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\ClientCollection\Models\ClientCollectionApplication>
 */
class ClientCollectionApplicationFactory extends Factory
{
    protected $model = \App\Modules\ClientCollection\Models\ClientCollectionApplication::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'sales_invoice_id' => SalesInvoice::factory(),
            'source_type' => ClientCollection::APPLICATION_SOURCE,
            'source_id' => ClientCollection::factory(),
            'applied_amount' => 0,
            'applied_at' => now(),
            'exchange_rate' => 1,
            'exchange_difference' => 0,
            'status' => 'active',
            'created_by' => User::factory(),
        ];
    }

    public function reversed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'reversed',
        ]);
    }
}
