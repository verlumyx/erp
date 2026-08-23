<?php

namespace Database\Factories;

use App\Modules\Company\Models\Company;
use App\Modules\PurchaseInvoice\Models\PurchaseInvoice;
use App\Modules\SupplierPayment\Models\SupplierPayment;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\SupplierPayment\Models\SupplierPaymentApplication>
 */
class SupplierPaymentApplicationFactory extends Factory
{
    protected $model = \App\Modules\SupplierPayment\Models\SupplierPaymentApplication::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'purchase_invoice_id' => PurchaseInvoice::factory(),
            'source_type' => SupplierPayment::APPLICATION_SOURCE,
            'source_id' => SupplierPayment::factory(),
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
