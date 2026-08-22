<?php

declare(strict_types=1);

use App\Modules\PurchaseInvoice\Models\PurchaseInvoice;
use App\Modules\Supplier\Models\Supplier;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

test('the list only shows invoices from the active company', function () {
    [$user, $company, $supplier, $warehouse] = purchaseInvoiceScenario();

    PurchaseInvoice::factory()->count(2)->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
    ]);

    PurchaseInvoice::factory()->create();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('purchase-invoices.index', ['company' => $company->id]))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('purchase-invoices/index')
                ->has('purchaseInvoices', 2)
                ->where('meta.total', 2)
        );
});

test('the list can be filtered by supplier, status and payment status', function () {
    [$user, $company, $supplier, $warehouse] = purchaseInvoiceScenario();

    $other = Supplier::factory()->create(['company_id' => $company->id]);

    PurchaseInvoice::factory()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'status' => 'draft',
    ]);

    PurchaseInvoice::factory()->confirmed()->create([
        'company_id' => $company->id,
        'supplier_id' => $other->id,
        'warehouse_id' => $warehouse->id,
        'payment_status' => 'paid',
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('purchase-invoices.index', ['company' => $company->id, 'supplier_id' => $other->id]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->has('purchaseInvoices', 1)->where('meta.total', 1));

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('purchase-invoices.index', ['company' => $company->id, 'status' => 'draft']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->has('purchaseInvoices', 1)->where('meta.total', 1));

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('purchase-invoices.index', ['company' => $company->id, 'payment_status' => 'paid']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->has('purchaseInvoices', 1)->where('meta.total', 1));
});

test('the list can be filtered by the printed number of the supplier', function () {
    [$user, $company, $supplier, $warehouse] = purchaseInvoiceScenario();

    PurchaseInvoice::factory()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'supplier_invoice_number' => '00-424242',
    ]);

    PurchaseInvoice::factory()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'supplier_invoice_number' => '00-999999',
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('purchase-invoices.index', [
            'company' => $company->id,
            'supplier_invoice_number' => '424242',
        ]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->has('purchaseInvoices', 1)->where('meta.total', 1));
});

test('the list carries the catalogs that feed the form selects', function () {
    [$user, $company] = purchaseInvoiceScenario();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('purchase-invoices.index', ['company' => $company->id]))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->has('options.warehouses', 1)
                /* Ni los artículos ni los proveedores viajan: se buscan contra su lookup. */
                ->missing('options.items')
                ->missing('options.suppliers')
        );
});

test('a user without permission cannot list purchase invoices', function () {
    [$user, $company] = purchaseInvoiceScenario();
    assignRoleWithPermissions($user, $company, ['suppliers.list']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('purchase-invoices.index', ['company' => $company->id]))
        ->assertForbidden();
});
