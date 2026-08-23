<?php

declare(strict_types=1);

use App\Modules\PurchaseReturn\Models\PurchaseReturn;
use App\Modules\Supplier\Models\Supplier;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

test('the list only shows returns from the active company', function () {
    [$user, $company, $supplier, $warehouse] = purchaseReturnScenario();

    PurchaseReturn::factory()->count(2)->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
    ]);

    PurchaseReturn::factory()->create();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('purchase-returns.index', ['company' => $company->id]))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('purchase-returns/index')
                ->has('purchaseReturns', 2)
                ->where('meta.total', 2)
        );
});

test('the list can be filtered by supplier, reason and status', function () {
    [$user, $company, $supplier, $warehouse] = purchaseReturnScenario();

    $other = Supplier::factory()->create(['company_id' => $company->id]);

    PurchaseReturn::factory()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'reason' => 'damaged',
        'status' => 'draft',
    ]);

    PurchaseReturn::factory()->confirmed()->create([
        'company_id' => $company->id,
        'supplier_id' => $other->id,
        'warehouse_id' => $warehouse->id,
        'reason' => 'excess',
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('purchase-returns.index', ['company' => $company->id, 'supplier_id' => $other->id]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->has('purchaseReturns', 1)->where('meta.total', 1));

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('purchase-returns.index', ['company' => $company->id, 'reason' => 'excess']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->has('purchaseReturns', 1)->where('meta.total', 1));

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('purchase-returns.index', ['company' => $company->id, 'status' => 'draft']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->has('purchaseReturns', 1)->where('meta.total', 1));
});

test('the list can be filtered by the tracking number of the return', function () {
    [$user, $company, $supplier, $warehouse] = purchaseReturnScenario();

    PurchaseReturn::factory()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'tracking_number' => 'ZM-424242',
    ]);

    PurchaseReturn::factory()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'tracking_number' => 'ZM-999999',
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('purchase-returns.index', [
            'company' => $company->id,
            'tracking_number' => '424242',
        ]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->has('purchaseReturns', 1)->where('meta.total', 1));
});

test('the list can be filtered by the source invoice and by date', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    $invoice = createPurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);

    PurchaseReturn::factory()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'purchase_invoice_id' => $invoice->id,
        'return_date' => now()->toDateString(),
    ]);

    PurchaseReturn::factory()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'return_date' => now()->subMonth()->toDateString(),
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('purchase-returns.index', [
            'company' => $company->id,
            'purchase_invoice_id' => $invoice->id,
        ]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->has('purchaseReturns', 1)->where('meta.total', 1));

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('purchase-returns.index', [
            'company' => $company->id,
            'date_from' => now()->subWeek()->toDateString(),
        ]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->has('purchaseReturns', 1)->where('meta.total', 1));
});

test('the list names the supplier, the warehouse and the source invoice', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    $invoice = createPurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);

    PurchaseReturn::factory()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'purchase_invoice_id' => $invoice->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('purchase-returns.index', ['company' => $company->id]))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->where('purchaseReturns.0.supplier_name', $supplier->name)
                ->where('purchaseReturns.0.warehouse_name', $warehouse->name)
                ->where('purchaseReturns.0.purchase_invoice_code', $invoice->code)
        );
});

test('a user without permission cannot list purchase returns', function () {
    [$user, $company] = purchaseReturnScenario();
    assignRoleWithPermissions($user, $company, ['suppliers.list']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('purchase-returns.index', ['company' => $company->id]))
        ->assertForbidden();
});
