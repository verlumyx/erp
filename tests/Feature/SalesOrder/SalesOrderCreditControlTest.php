<?php

declare(strict_types=1);

use App\Modules\Client\Models\Client;
use App\Modules\Item\Models\Item;
use App\Modules\SalesOrder\Models\SalesOrder;

use function Pest\Laravel\actingAs;

/** Todos los permisos del módulo menos los dos que levantan un bloqueo. */
const SALES_ORDER_BASE_PERMISSIONS = [
    'sales-orders.list',
    'sales-orders.create',
    'sales-orders.show',
    'sales-orders.update',
    'sales-orders.update-status',
];

test('a credit sale beyond the limit of the client is blocked', function () {
    [$user, $company, , $warehouse, $item, $unit] = salesOrderScenario();

    stockSalesOrderWarehouse($user, $company, $warehouse, $item);

    /** Debe 150 y su límite son 200: un pedido de 200 no cabe. */
    $client = Client::factory()->create([
        'company_id' => $company->id,
        'current_balance' => 150,
        'credit_limit' => 200,
    ]);

    $order = createSalesOrder($user, $company, $client, $warehouse, $item, $unit, [
        'payment_term_days' => 30,
    ]);

    assignRoleWithPermissions($user, $company, SALES_ORDER_BASE_PERMISSIONS);

    putStatus($user, $company, $order, ['status' => 'confirmed'])
        ->assertSessionHasErrors('status');

    expect(SalesOrder::find($order->id)->status)->toBe('draft');
});

test('the override permission lifts the block and stamps who approved it', function () {
    [$user, $company, , $warehouse, $item, $unit] = salesOrderScenario();

    stockSalesOrderWarehouse($user, $company, $warehouse, $item);

    $client = Client::factory()->create([
        'company_id' => $company->id,
        'current_balance' => 150,
        'credit_limit' => 200,
    ]);

    $order = createSalesOrder($user, $company, $client, $warehouse, $item, $unit, [
        'payment_term_days' => 30,
    ]);

    assignRoleWithPermissions($user, $company, [
        ...SALES_ORDER_BASE_PERMISSIONS,
        'sales-orders.override-credit-limit',
    ]);

    putStatus($user, $company, $order, ['status' => 'confirmed'])->assertSessionHasNoErrors();

    $confirmed = SalesOrder::find($order->id);
    expect($confirmed->status)->toBe('confirmed');
    expect($confirmed->approved_by)->toBe($user->id);
    expect($confirmed->approved_at)->not->toBeNull();
});

test('a blocked client cannot be sold to, on credit or not', function () {
    [$user, $company, , $warehouse, $item, $unit] = salesOrderScenario();

    stockSalesOrderWarehouse($user, $company, $warehouse, $item);

    $client = Client::factory()->create([
        'company_id' => $company->id,
        'credit_blocked' => 'yes',
        'credit_limit' => 100000,
    ]);

    /** Contado: no consume crédito, pero el bloqueo pesa igual. */
    $order = createSalesOrder($user, $company, $client, $warehouse, $item, $unit, [
        'payment_term_days' => 0,
    ]);

    assignRoleWithPermissions($user, $company, SALES_ORDER_BASE_PERMISSIONS);

    putStatus($user, $company, $order, ['status' => 'confirmed'])
        ->assertSessionHasErrors('status');

    expect(SalesOrder::find($order->id)->status)->toBe('draft');
});

test('a cash sale does not consume the credit of the client', function () {
    [$user, $company, , $warehouse, $item, $unit] = salesOrderScenario();

    stockSalesOrderWarehouse($user, $company, $warehouse, $item);

    $client = Client::factory()->create([
        'company_id' => $company->id,
        'current_balance' => 150,
        'credit_limit' => 200,
    ]);

    $order = createSalesOrder($user, $company, $client, $warehouse, $item, $unit, [
        'payment_term_days' => 0,
    ]);

    assignRoleWithPermissions($user, $company, SALES_ORDER_BASE_PERMISSIONS);

    putStatus($user, $company, $order, ['status' => 'confirmed'])->assertSessionHasNoErrors();

    expect(SalesOrder::find($order->id)->status)->toBe('confirmed');
});

test('a client without a declared limit has no ceiling to exceed', function () {
    [$user, $company, , $warehouse, $item, $unit] = salesOrderScenario();

    stockSalesOrderWarehouse($user, $company, $warehouse, $item);

    $client = Client::factory()->create([
        'company_id' => $company->id,
        'current_balance' => 5000,
        'credit_limit' => 0,
    ]);

    $order = createSalesOrder($user, $company, $client, $warehouse, $item, $unit, [
        'payment_term_days' => 30,
    ]);

    assignRoleWithPermissions($user, $company, SALES_ORDER_BASE_PERMISSIONS);

    putStatus($user, $company, $order, ['status' => 'confirmed'])->assertSessionHasNoErrors();
});

test('selling below the minimum price of the item is rejected', function () {
    [$user, $company, $client, $warehouse, , $unit] = salesOrderScenario();

    $item = Item::factory()->create(['company_id' => $company->id, 'min_price' => 80]);

    \App\Modules\Item\Models\ItemUnit::factory()->base()->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
        'measurement_unit_id' => $unit->id,
    ]);

    assignRoleWithPermissions($user, $company, SALES_ORDER_BASE_PERMISSIONS);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('sales-orders.store', ['company' => $company->id]),
            salesOrderPayload($client, $warehouse, $item, $unit, [
                'lines' => [[
                    'item_id' => $item->id,
                    'measurement_unit_id' => $unit->id,
                    'quantity' => 2,
                    'unit_price' => 50,
                ]],
            ]),
        )
        ->assertSessionHasErrors('lines.0.unit_price');
});

test('the override permission allows selling below the minimum price', function () {
    [$user, $company, $client, $warehouse, , $unit] = salesOrderScenario();

    $item = Item::factory()->create(['company_id' => $company->id, 'min_price' => 80]);

    \App\Modules\Item\Models\ItemUnit::factory()->base()->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
        'measurement_unit_id' => $unit->id,
    ]);

    assignRoleWithPermissions($user, $company, [
        ...SALES_ORDER_BASE_PERMISSIONS,
        'sales-orders.override-min-price',
    ]);

    $payload = salesOrderPayload($client, $warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 2,
            'unit_price' => 50,
        ]],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('sales-orders.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    expect(SalesOrder::find($payload['id']))->not->toBeNull();
});
