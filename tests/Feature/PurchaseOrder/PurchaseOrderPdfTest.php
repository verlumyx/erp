<?php

declare(strict_types=1);

use App\Modules\PurchaseOrder\Models\PurchaseOrder;
use App\Modules\PurchaseOrder\Models\PurchaseOrderLine;
use App\Modules\Role\Models\Role;

use function Pest\Laravel\actingAs;

test('a purchase order in draft cannot be printed', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseOrderScenario();

    $order = PurchaseOrder::factory()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'created_by' => $user->id,
        'status' => 'draft',
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('purchase-orders.pdf', ['company' => $company->id, 'id' => $order->id]))
        ->assertForbidden();
});

test('an approved purchase order pdf can be generated and streamed', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseOrderScenario();

    $order = PurchaseOrder::factory()->confirmed()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'created_by' => $user->id,
        'approved_by' => $user->id,
        'subtotal' => 100,
        'tax_amount' => 16,
        'total' => 116,
    ]);

    PurchaseOrderLine::factory()->create([
        'company_id' => $company->id,
        'purchase_order_id' => $order->id,
        'item_id' => $item->id,
        'measurement_unit_id' => $unit->id,
        'quantity' => 2,
        'unit_price' => 50,
        'subtotal' => 100,
        'tax_percent' => 16,
        'tax_amount' => 16,
        'total' => 116,
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('purchase-orders.pdf', ['company' => $company->id, 'id' => $order->id]));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/pdf');
    expect($response->headers->get('Content-Disposition'))->toContain("orden-compra-{$order->code}.pdf");
});

test('a user without permission cannot download the purchase order pdf', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseOrderScenario();

    $order = PurchaseOrder::factory()->confirmed()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'created_by' => $user->id,
    ]);

    $role = Role::create([
        'company_id' => $company->id,
        'name' => 'No access '.uniqid(),
        'status' => 'active',
        'permission_type' => 'specific',
    ]);

    $user->companies()->updateExistingPivot($company->id, ['role_id' => $role->id]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('purchase-orders.pdf', ['company' => $company->id, 'id' => $order->id]))
        ->assertForbidden();
});
