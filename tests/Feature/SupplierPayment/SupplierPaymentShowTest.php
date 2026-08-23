<?php

declare(strict_types=1);

use App\Modules\Company\Models\Company;
use App\Modules\Supplier\Models\Supplier;
use App\Modules\SupplierPayment\Models\SupplierPayment;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

test('the detail carries the payment with its distribution', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = supplierPaymentScenario();

    $invoice = payablePurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);

    $payment = createSupplierPayment($user, $company, $supplier, [
        'amount' => 250,
        'applications' => [
            ['purchase_invoice_id' => $invoice->id, 'applied_amount' => 250],
        ],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('supplier-payments.show', ['company' => $company->id, 'id' => $payment->id]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('supplier-payments/show')
            ->where('supplierPayment.code', 'PGP000001')
            ->where('supplierPayment.supplier_name', $supplier->name)
            ->has('supplierPayment.applications', 1)
            ->where('supplierPayment.applications.0.purchase_invoice_code', $invoice->code));
});

test('a payment of another company is not reachable', function () {
    [$user, $company] = supplierPaymentScenario();

    $otherCompany = Company::create([
        'name' => 'Otra empresa',
        'status' => 'active',
        'created_by' => $user->id,
    ]);

    $payment = SupplierPayment::factory()->create([
        'company_id' => $otherCompany->id,
        'supplier_id' => Supplier::factory()->create(['company_id' => $otherCompany->id])->id,
        'created_by' => $user->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('supplier-payments.show', ['company' => $company->id, 'id' => $payment->id]))
        ->assertNotFound();
});

test('the edit form is rendered for a draft payment', function () {
    [$user, $company, $supplier] = supplierPaymentScenario();

    $payment = createSupplierPayment($user, $company, $supplier);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('supplier-payments.edit', ['company' => $company->id, 'id' => $payment->id]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('supplier-payments/edit')
            ->where('supplierPayment.id', $payment->id));
});

test('a user without permission cannot see the detail', function () {
    [$user, $company, $supplier] = supplierPaymentScenario();

    $payment = createSupplierPayment($user, $company, $supplier);

    assignRoleWithPermissions($user, $company, ['supplier-payments.list']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('supplier-payments.show', ['company' => $company->id, 'id' => $payment->id]))
        ->assertForbidden();
});
