<?php

declare(strict_types=1);

use App\Modules\PurchaseOrder\Models\PurchaseOrder;
use App\Modules\PurchaseOrder\Models\PurchaseOrderLine;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

test('the detail shows the order with its lines', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseOrderScenario();

    $order = PurchaseOrder::factory()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'created_by' => $user->id,
    ]);

    PurchaseOrderLine::factory()->create([
        'company_id' => $company->id,
        'purchase_order_id' => $order->id,
        'item_id' => $item->id,
        'measurement_unit_id' => $unit->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('purchase-orders.show', ['company' => $company->id, 'id' => $order->id]))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('purchase-orders/show')
                ->where('purchaseOrder.id', $order->id)
                ->where('purchaseOrder.supplier_name', $supplier->name)
                ->where('purchaseOrder.warehouse_name', $warehouse->name)
                ->has('purchaseOrder.lines', 1)
                ->where('purchaseOrder.lines.0.item_name', $item->name)
        );
});

test('each line carries what it owes on both roads', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseOrderScenario();

    $order = PurchaseOrder::factory()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'created_by' => $user->id,
    ]);

    PurchaseOrderLine::factory()->create([
        'company_id' => $company->id,
        'purchase_order_id' => $order->id,
        'item_id' => $item->id,
        'measurement_unit_id' => $unit->id,
        'quantity' => 10,
        'received_quantity' => 4,
        'pending_quantity' => 6,
        'invoiced_quantity' => 7,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('purchase-orders.show', ['company' => $company->id, 'id' => $order->id]))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->where('purchaseOrder.lines.0.pending_quantity', '6.0000')
                ->where('purchaseOrder.lines.0.pending_invoiced_quantity', '3.0000')
        );
});

test('a line already over-invoiced owes nothing', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseOrderScenario();

    $order = PurchaseOrder::factory()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'created_by' => $user->id,
    ]);

    PurchaseOrderLine::factory()->create([
        'company_id' => $company->id,
        'purchase_order_id' => $order->id,
        'item_id' => $item->id,
        'measurement_unit_id' => $unit->id,
        'quantity' => 5,
        'invoiced_quantity' => 8,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('purchase-orders.show', ['company' => $company->id, 'id' => $order->id]))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->where('purchaseOrder.lines.0.pending_invoiced_quantity', '0.0000')
        );
});

test('an order from another company is not found', function () {
    [$user, $company] = purchaseOrderScenario();

    $foreign = PurchaseOrder::factory()->create();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('purchase-orders.show', ['company' => $company->id, 'id' => $foreign->id]))
        ->assertNotFound();
});

test('the edit form loads the order and its catalogs', function () {
    [$user, $company, $supplier, $warehouse] = purchaseOrderScenario();

    $order = PurchaseOrder::factory()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'created_by' => $user->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('purchase-orders.edit', ['company' => $company->id, 'id' => $order->id]))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('purchase-orders/edit')
                ->where('purchaseOrder.id', $order->id)
                ->has('options.warehouses')
                /* Ni los artículos ni los proveedores viajan: se buscan contra su lookup. */
                ->missing('options.items')
                ->missing('options.suppliers')
        );
});

test('a user without permission cannot see the detail', function () {
    [$user, $company, $supplier, $warehouse] = purchaseOrderScenario();

    $order = PurchaseOrder::factory()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
    ]);

    assignRoleWithPermissions($user, $company, ['purchase-orders.list']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('purchase-orders.show', ['company' => $company->id, 'id' => $order->id]))
        ->assertForbidden();
});
