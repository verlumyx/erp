<?php

declare(strict_types=1);

use App\Modules\Company\Models\Company;
use App\Modules\ExchangeRate\Models\ExchangeRate;
use App\Modules\Supplier\Models\Supplier;
use App\Modules\SupplierPayment\Models\SupplierPayment;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

test('the list only shows the payments of the active company', function () {
    [$user, $company, $supplier] = supplierPaymentScenario();

    createSupplierPayment($user, $company, $supplier);

    $otherCompany = Company::create([
        'name' => 'Otra empresa',
        'status' => 'active',
        'created_by' => $user->id,
    ]);

    SupplierPayment::factory()->create([
        'company_id' => $otherCompany->id,
        'supplier_id' => Supplier::factory()->create(['company_id' => $otherCompany->id])->id,
        'created_by' => $user->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('supplier-payments.index', ['company' => $company->id]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('supplier-payments/index')
            ->has('supplierPayments', 1)
            ->where('meta.total', 1));
});

test('the list filters by supplier, status and payment method', function () {
    [$user, $company, $supplier] = supplierPaymentScenario();

    $other = Supplier::factory()->create(['company_id' => $company->id]);

    createSupplierPayment($user, $company, $supplier, ['payment_method' => 'transfer']);
    createSupplierPayment($user, $company, $other, ['payment_method' => 'cash']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('supplier-payments.index', [
            'company' => $company->id,
            'supplier_id' => $supplier->id,
        ]))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('supplierPayments', 1));

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('supplier-payments.index', [
            'company' => $company->id,
            'payment_method' => 'cash',
        ]))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('supplierPayments', 1));

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('supplier-payments.index', [
            'company' => $company->id,
            'status' => 'confirmed',
        ]))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('supplierPayments', 0));
});

test('the list filters by date range', function () {
    [$user, $company, $supplier] = supplierPaymentScenario();

    /** Un pago con fecha vieja se valora con la tasa de su día, no con la de hoy. */
    ExchangeRate::factory()->create([
        'company_id' => $company->id,
        'currency' => 'USD',
        'rate_date' => now()->subDays(10)->toDateString(),
        'rate' => 35.0,
        'type' => 'legal',
        'status' => 'active',
        'created_by' => $user->id,
    ]);

    createSupplierPayment($user, $company, $supplier, [
        'payment_date' => now()->subDays(10)->toDateString(),
    ]);
    createSupplierPayment($user, $company, $supplier);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('supplier-payments.index', [
            'company' => $company->id,
            'date_from' => now()->subDay()->toDateString(),
        ]))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('supplierPayments', 1));
});

test('a user without permission cannot list the payments', function () {
    [$user, $company] = supplierPaymentScenario();

    assignRoleWithPermissions($user, $company, ['suppliers.list']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('supplier-payments.index', ['company' => $company->id]))
        ->assertForbidden();
});
