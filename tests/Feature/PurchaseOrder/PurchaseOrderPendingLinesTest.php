<?php

declare(strict_types=1);

use App\Modules\Company\Models\Company;
use App\Modules\PurchaseOrder\Models\PurchaseOrder;
use App\Modules\PurchaseOrder\Models\PurchaseOrderLine;

use function Pest\Laravel\actingAs;

/**
 * Lo que a una orden le queda por cubrir.
 *
 * Son dos preguntas distintas sobre la misma orden: la factura de compra pide
 * lo que falta por facturar y la entrada lo que falta por llegar. Cada una
 * tiene su ruta y las dos miden contra su propio avance.
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

test('the receivable lines measure the saldo against what was received', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseOrderScenario();

    $order = PurchaseOrder::factory()->confirmed()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
    ]);

    /* La misma línea debe 6 por facturar y solo 3 por llegar. */
    $line = PurchaseOrderLine::factory()->create([
        'company_id' => $company->id,
        'purchase_order_id' => $order->id,
        'line_number' => 1,
        'item_id' => $item->id,
        'measurement_unit_id' => $unit->id,
        'quantity' => 10,
        'invoiced_quantity' => 4,
        'received_quantity' => 7,
    ]);

    $response = actingAs($user)->withSession(['current_company_id' => $company->id])
        ->getJson(route('purchase-orders.receivable-lines', [
            'company' => $company->id,
            'id' => $order->id,
        ]));

    $response->assertOk();
    expect($response->json('data.0.id'))->toBe($line->id);
    expect((float) $response->json('data.0.received_quantity'))->toBe(7.0);
    expect((float) $response->json('data.0.invoiced_quantity'))->toBe(4.0);
    /* Contra lo recibido, no contra lo facturado. */
    expect((float) $response->json('data.0.pending_quantity'))->toBe(3.0);
});

test('a line already received in full is left out unless the document already had it', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseOrderScenario();

    $order = PurchaseOrder::factory()->confirmed()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
    ]);

    $settled = PurchaseOrderLine::factory()->create([
        'company_id' => $company->id,
        'purchase_order_id' => $order->id,
        'line_number' => 1,
        'item_id' => $item->id,
        'measurement_unit_id' => $unit->id,
        'quantity' => 4,
        'received_quantity' => 4,
    ]);

    $url = route('purchase-orders.receivable-lines', [
        'company' => $company->id,
        'id' => $order->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->getJson($url)
        ->assertOk()
        ->assertJsonCount(0, 'data');

    /*
     * La entrada que ya la traía atada sí la recibe de vuelta: sin ella su
     * pantalla no sabría a qué apunta esa línea.
     */
    $response = actingAs($user)->withSession(['current_company_id' => $company->id])
        ->getJson($url.'?ids='.$settled->id);

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.id'))->toBe($settled->id);
    expect((float) $response->json('data.0.pending_quantity'))->toBe(0.0);
});
