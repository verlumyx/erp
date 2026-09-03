<?php

declare(strict_types=1);

use App\Modules\Company\Models\Company;
use App\Modules\PurchaseOrder\Models\PurchaseOrder;
use App\Modules\PurchaseOrder\Models\PurchaseOrderLine;

use function Pest\Laravel\actingAs;

/**
 * Lo que a una orden le queda por facturar.
 *
 * Lo pide la pantalla de la factura de compra en cuanto se elige la orden, y de
 * ahí salen sus líneas.
 */
test('it returns the lines with something left to invoice', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseOrderScenario();

    $order = PurchaseOrder::factory()->confirmed()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
    ]);

    $line = PurchaseOrderLine::factory()->create([
        'company_id' => $company->id,
        'purchase_order_id' => $order->id,
        'line_number' => 1,
        'item_id' => $item->id,
        'measurement_unit_id' => $unit->id,
        'quantity' => 10,
        'invoiced_quantity' => 4,
        'unit_price' => 12.5,
    ]);

    $response = actingAs($user)->withSession(['current_company_id' => $company->id])
        ->getJson(route('purchase-orders.invoiceable-lines', [
            'company' => $company->id,
            'id' => $order->id,
        ]));

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.id'))->toBe($line->id);
    expect($response->json('data.0.item_id'))->toBe($item->id);
    expect($response->json('data.0.item_code'))->toBe($item->code);
    expect($response->json('data.0.measurement_unit_id'))->toBe($unit->id);
    expect((float) $response->json('data.0.quantity'))->toBe(10.0);
    expect((float) $response->json('data.0.invoiced_quantity'))->toBe(4.0);
    /* Lo que queda: 10 pedidas menos 4 ya facturadas. */
    expect((float) $response->json('data.0.pending_quantity'))->toBe(6.0);
    expect((float) $response->json('data.0.unit_price'))->toBe(12.5);
});

test('it leaves out the lines already invoiced in full and the inactive ones', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseOrderScenario();

    $order = PurchaseOrder::factory()->confirmed()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
    ]);

    $pending = PurchaseOrderLine::factory()->create([
        'company_id' => $company->id,
        'purchase_order_id' => $order->id,
        'line_number' => 1,
        'item_id' => $item->id,
        'measurement_unit_id' => $unit->id,
        'quantity' => 5,
        'invoiced_quantity' => 0,
    ]);

    /** Ya facturada entera: no vuelve a ofrecerse. */
    PurchaseOrderLine::factory()->create([
        'company_id' => $company->id,
        'purchase_order_id' => $order->id,
        'line_number' => 2,
        'item_id' => $item->id,
        'measurement_unit_id' => $unit->id,
        'quantity' => 3,
        'invoiced_quantity' => 3,
    ]);

    /** Desactivada por la política de no borrado: tampoco. */
    PurchaseOrderLine::factory()->create([
        'company_id' => $company->id,
        'purchase_order_id' => $order->id,
        'line_number' => 3,
        'item_id' => $item->id,
        'measurement_unit_id' => $unit->id,
        'quantity' => 7,
        'invoiced_quantity' => 0,
        'status' => 'inactive',
    ]);

    $response = actingAs($user)->withSession(['current_company_id' => $company->id])
        ->getJson(route('purchase-orders.invoiceable-lines', [
            'company' => $company->id,
            'id' => $order->id,
        ]));

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.id'))->toBe($pending->id);
});

test('an order from another company is not found', function () {
    [$user, $company, $supplier, $warehouse] = purchaseOrderScenario();

    $other = Company::create([
        'name' => 'Otra '.uniqid(),
        'status' => 'active',
        'created_by' => $user->id,
    ]);

    $order = PurchaseOrder::factory()->confirmed()->create([
        'company_id' => $other->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->getJson(route('purchase-orders.invoiceable-lines', [
            'company' => $company->id,
            'id' => $order->id,
        ]))
        ->assertNotFound();
});

test('a user without permission cannot ask what is left to invoice', function () {
    [$user, $company, $supplier, $warehouse] = purchaseOrderScenario();
    assignRoleWithPermissions($user, $company, ['suppliers.list']);

    $order = PurchaseOrder::factory()->confirmed()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->getJson(route('purchase-orders.invoiceable-lines', [
            'company' => $company->id,
            'id' => $order->id,
        ]))
        ->assertForbidden();
});
