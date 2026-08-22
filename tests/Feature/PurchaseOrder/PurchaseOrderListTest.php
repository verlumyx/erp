<?php

declare(strict_types=1);

use App\Modules\PurchaseOrder\Models\PurchaseOrder;
use App\Modules\Supplier\Models\Supplier;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

test('the list only shows orders from the active company', function () {
    [$user, $company, $supplier, $warehouse] = purchaseOrderScenario();

    PurchaseOrder::factory()->count(2)->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
    ]);

    PurchaseOrder::factory()->create();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('purchase-orders.index', ['company' => $company->id]))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('purchase-orders/index')
                ->has('purchaseOrders', 2)
                ->where('meta.total', 2)
        );
});

test('the list can be filtered by supplier and by status', function () {
    [$user, $company, $supplier, $warehouse] = purchaseOrderScenario();

    $other = Supplier::factory()->create(['company_id' => $company->id]);

    PurchaseOrder::factory()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'status' => 'draft',
    ]);

    PurchaseOrder::factory()->confirmed()->create([
        'company_id' => $company->id,
        'supplier_id' => $other->id,
        'warehouse_id' => $warehouse->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('purchase-orders.index', ['company' => $company->id, 'supplier_id' => $other->id]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->has('purchaseOrders', 1)->where('meta.total', 1));

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('purchase-orders.index', ['company' => $company->id, 'status' => 'draft']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->has('purchaseOrders', 1)->where('meta.total', 1));
});

test('the list carries the catalogs that feed the form selects', function () {
    [$user, $company] = purchaseOrderScenario();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('purchase-orders.index', ['company' => $company->id]))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->has('options.suppliers', 1)
                ->has('options.warehouses', 1)
                /* El catálogo de artículos ya no viaja: la línea lo busca contra items.options. */
                ->missing('options.items')
        );
});

test('a user without permission cannot list purchase orders', function () {
    [$user, $company] = purchaseOrderScenario();
    assignRoleWithPermissions($user, $company, ['suppliers.list']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('purchase-orders.index', ['company' => $company->id]))
        ->assertForbidden();
});
