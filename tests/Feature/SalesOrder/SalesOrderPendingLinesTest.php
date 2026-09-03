<?php

declare(strict_types=1);

use App\Modules\Company\Models\Company;
use App\Modules\Item\Models\Item;
use App\Modules\SalesOrder\Models\SalesOrder;
use App\Modules\SalesOrder\Models\SalesOrderLine;

use function Pest\Laravel\actingAs;

/**
 * Lo que a un pedido le queda por cubrir.
 *
 * Son dos preguntas distintas sobre el mismo pedido: la factura de venta pide
 * lo que falta por facturar y el despacho lo que falta por salir. Cada una
 * tiene su ruta y las dos miden contra su propio avance.
 */
test('it returns the lines with something left to invoice', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    $order = SalesOrder::factory()->confirmed()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'warehouse_id' => $warehouse->id,
    ]);

    $line = SalesOrderLine::factory()->create([
        'company_id' => $company->id,
        'sales_order_id' => $order->id,
        'line_number' => 1,
        'item_id' => $item->id,
        'measurement_unit_id' => $unit->id,
        'quantity' => 10,
        'invoiced_quantity' => 4,
        'unit_price' => 12.5,
    ]);

    $response = actingAs($user)->withSession(['current_company_id' => $company->id])
        ->getJson(route('sales-orders.invoiceable-lines', [
            'company' => $company->id,
            'id' => $order->id,
        ]));

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.id'))->toBe($line->id);
    expect($response->json('data.0.item_id'))->toBe($item->id);
    expect($response->json('data.0.item_sku'))->toBe($item->sku);
    expect($response->json('data.0.measurement_unit_id'))->toBe($unit->id);
    expect((float) $response->json('data.0.quantity'))->toBe(10.0);
    expect((float) $response->json('data.0.invoiced_quantity'))->toBe(4.0);
    /* Lo que queda: 10 pedidas menos 4 ya facturadas. */
    expect((float) $response->json('data.0.pending_quantity'))->toBe(6.0);
    expect((float) $response->json('data.0.unit_price'))->toBe(12.5);
});

test('it leaves out the lines already invoiced in full and the inactive ones', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    $order = SalesOrder::factory()->confirmed()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'warehouse_id' => $warehouse->id,
    ]);

    $pending = SalesOrderLine::factory()->create([
        'company_id' => $company->id,
        'sales_order_id' => $order->id,
        'line_number' => 1,
        'item_id' => $item->id,
        'measurement_unit_id' => $unit->id,
        'quantity' => 5,
        'invoiced_quantity' => 0,
    ]);

    /** Ya facturada entera: no vuelve a ofrecerse. */
    SalesOrderLine::factory()->create([
        'company_id' => $company->id,
        'sales_order_id' => $order->id,
        'line_number' => 2,
        'item_id' => $item->id,
        'measurement_unit_id' => $unit->id,
        'quantity' => 3,
        'invoiced_quantity' => 3,
    ]);

    /** Desactivada por la política de no borrado: tampoco. */
    SalesOrderLine::factory()->create([
        'company_id' => $company->id,
        'sales_order_id' => $order->id,
        'line_number' => 3,
        'item_id' => $item->id,
        'measurement_unit_id' => $unit->id,
        'quantity' => 7,
        'invoiced_quantity' => 0,
        'status' => 'inactive',
    ]);

    $response = actingAs($user)->withSession(['current_company_id' => $company->id])
        ->getJson(route('sales-orders.invoiceable-lines', [
            'company' => $company->id,
            'id' => $order->id,
        ]));

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.id'))->toBe($pending->id);
});

test('an order from another company is not found', function () {
    [$user, $company, $client, $warehouse] = salesOrderScenario();

    $other = Company::create([
        'name' => 'Otra '.uniqid(),
        'status' => 'active',
        'created_by' => $user->id,
    ]);

    $order = SalesOrder::factory()->confirmed()->create([
        'company_id' => $other->id,
        'client_id' => $client->id,
        'warehouse_id' => $warehouse->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->getJson(route('sales-orders.invoiceable-lines', [
            'company' => $company->id,
            'id' => $order->id,
        ]))
        ->assertNotFound();
});

test('a user without permission cannot ask what is left to invoice', function () {
    [$user, $company, $client, $warehouse] = salesOrderScenario();
    assignRoleWithPermissions($user, $company, ['clients.list']);

    $order = SalesOrder::factory()->confirmed()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'warehouse_id' => $warehouse->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->getJson(route('sales-orders.invoiceable-lines', [
            'company' => $company->id,
            'id' => $order->id,
        ]))
        ->assertForbidden();
});

test('the dispatchable lines measure the saldo against what was dispatched', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    $order = SalesOrder::factory()->confirmed()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'warehouse_id' => $warehouse->id,
    ]);

    /* La misma línea debe 6 por facturar y solo 3 por salir. */
    $line = SalesOrderLine::factory()->create([
        'company_id' => $company->id,
        'sales_order_id' => $order->id,
        'line_number' => 1,
        'item_id' => $item->id,
        'measurement_unit_id' => $unit->id,
        'quantity' => 10,
        'invoiced_quantity' => 4,
        'dispatched_quantity' => 7,
    ]);

    $response = actingAs($user)->withSession(['current_company_id' => $company->id])
        ->getJson(route('sales-orders.dispatchable-lines', [
            'company' => $company->id,
            'id' => $order->id,
        ]));

    $response->assertOk();
    expect($response->json('data.0.id'))->toBe($line->id);
    expect((float) $response->json('data.0.dispatched_quantity'))->toBe(7.0);
    expect((float) $response->json('data.0.invoiced_quantity'))->toBe(4.0);
    /* Contra lo despachado, no contra lo facturado. */
    expect((float) $response->json('data.0.pending_quantity'))->toBe(3.0);
});

test('a line already dispatched in full is left out unless the document already had it', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    $order = SalesOrder::factory()->confirmed()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'warehouse_id' => $warehouse->id,
    ]);

    $settled = SalesOrderLine::factory()->create([
        'company_id' => $company->id,
        'sales_order_id' => $order->id,
        'line_number' => 1,
        'item_id' => $item->id,
        'measurement_unit_id' => $unit->id,
        'quantity' => 4,
        'dispatched_quantity' => 4,
    ]);

    $url = route('sales-orders.dispatchable-lines', [
        'company' => $company->id,
        'id' => $order->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->getJson($url)
        ->assertOk()
        ->assertJsonCount(0, 'data');

    /*
     * El despacho que ya la traía atada sí la recibe de vuelta: sin ella su
     * pantalla no sabría a qué apunta esa línea.
     */
    $response = actingAs($user)->withSession(['current_company_id' => $company->id])
        ->getJson($url.'?ids='.$settled->id);

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.id'))->toBe($settled->id);
    expect((float) $response->json('data.0.pending_quantity'))->toBe(0.0);
});

/**
 * Un servicio se factura pero no sale en un bulto: el despacho no lo lleva y
 * la factura sí lo cobra.
 */
test('an item that carries no stock is left out of the dispatchable lines but not the invoiceable ones', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    $service = Item::factory()->create([
        'company_id' => $company->id,
        'type' => 'service',
    ]);

    $order = SalesOrder::factory()->confirmed()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'warehouse_id' => $warehouse->id,
    ]);

    $goods = SalesOrderLine::factory()->create([
        'company_id' => $company->id,
        'sales_order_id' => $order->id,
        'line_number' => 1,
        'item_id' => $item->id,
        'measurement_unit_id' => $unit->id,
        'quantity' => 5,
    ]);

    $labour = SalesOrderLine::factory()->create([
        'company_id' => $company->id,
        'sales_order_id' => $order->id,
        'line_number' => 2,
        'item_id' => $service->id,
        'measurement_unit_id' => $unit->id,
        'quantity' => 1,
    ]);

    $ask = fn (string $route): array => actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->getJson(route($route, ['company' => $company->id, 'id' => $order->id]))
        ->assertOk()
        ->json('data');

    /* El despacho saca mercancía: el servicio no está. */
    $dispatchable = $ask('sales-orders.dispatchable-lines');
    expect($dispatchable)->toHaveCount(1);
    expect($dispatchable[0]['id'])->toBe($goods->id);

    /* La factura cobra las dos. */
    $invoiceable = $ask('sales-orders.invoiceable-lines');
    expect(array_column($invoiceable, 'id'))
        ->toEqualCanonicalizing([$goods->id, $labour->id]);
});
