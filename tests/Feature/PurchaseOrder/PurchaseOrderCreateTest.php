<?php

declare(strict_types=1);

use App\Modules\Item\Models\Item;
use App\Modules\Item\Models\ItemUnit;
use App\Modules\MeasurementUnit\Models\MeasurementUnit;
use App\Modules\PurchaseOrder\Models\PurchaseOrder;
use App\Modules\Supplier\Models\Supplier;
use App\Modules\Warehouse\Models\Warehouse;

use function Pest\Laravel\actingAs;

test('a purchase order can be created', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseOrderScenario();

    $payload = purchaseOrderPayload($supplier, $warehouse, $item, $unit, [
        'expected_date' => now()->addDays(7)->toDateString(),
        'supplier_reference' => 'COT-889',
        'payment_term_days' => 30,
        'notes' => 'Entrega en horario de la mañana.',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('purchase-orders.store', ['company' => $company->id]), $payload);

    $response->assertRedirect(route('purchase-orders.index', ['company' => $company->id]));
    $response->assertSessionHasNoErrors();

    $order = PurchaseOrder::with('lines')->find($payload['id']);
    expect($order)->not->toBeNull();
    expect($order->code)->toBe('OCO000001');
    expect($order->status)->toBe('draft');
    expect($order->company_id)->toBe($company->id);
    expect($order->created_by)->toBe($user->id);
    expect($order->supplier_id)->toBe($supplier->id);
    expect($order->warehouse_id)->toBe($warehouse->id);
    expect($order->supplier_reference)->toBe('COT-889');
    expect($order->payment_term_days)->toBe(30);
    expect($order->lines)->toHaveCount(1);

    $line = $order->lines->first();
    expect($line->line_number)->toBe(1);
    expect($line->company_id)->toBe($company->id);
    expect((float) $line->quantity)->toBe(10.0);
    expect((float) $line->base_quantity)->toBe(10.0);
    expect((float) $line->pending_quantity)->toBe(10.0);
    expect((float) $line->subtotal)->toBe(250.0);
});

test('the totals are calculated on the backend and ignore what the client sends', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseOrderScenario();

    $payload = purchaseOrderPayload($supplier, $warehouse, $item, $unit, [
        'subtotal' => 999999,
        'total' => 999999,
        'discount_amount' => 50,
        'lines' => [
            [
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 10,
                'unit_price' => 100,
                'discount_percent' => 10,
                'tax_percent' => 16,
                'subtotal' => 1,
                'total' => 1,
            ],
        ],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('purchase-orders.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    $order = PurchaseOrder::with('lines')->find($payload['id']);
    $line = $order->lines->first();

    // 10 × 100 = 1000, −10 % = 900 de base; 16 % de impuesto = 144.
    expect((float) $line->discount_amount)->toBe(100.0);
    expect((float) $line->subtotal)->toBe(900.0);
    expect((float) $line->tax_amount)->toBe(144.0);
    expect((float) $line->total)->toBe(1044.0);

    // Cabecera: 900 de subtotal − 50 de descuento global + 144 de impuesto.
    expect((float) $order->subtotal)->toBe(900.0);
    expect((float) $order->discount_amount)->toBe(50.0);
    expect((float) $order->tax_amount)->toBe(144.0);
    expect((float) $order->total)->toBe(994.0);
});

test('base_quantity converts the line to the base unit of the item', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseOrderScenario();

    $box = MeasurementUnit::factory()->create(['company_id' => $company->id]);
    ItemUnit::factory()->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
        'measurement_unit_id' => $box->id,
        'is_base' => 'no',
        'conversion_factor' => 12,
    ]);

    $payload = purchaseOrderPayload($supplier, $warehouse, $item, $unit, [
        'lines' => [
            [
                'item_id' => $item->id,
                'measurement_unit_id' => $box->id,
                'quantity' => 5,
                'unit_price' => 10,
            ],
        ],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('purchase-orders.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    $line = PurchaseOrder::with('lines')->find($payload['id'])->lines->first();
    expect((float) $line->quantity)->toBe(5.0);
    expect((float) $line->base_quantity)->toBe(60.0);
});

test('the code auto-increments per company', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseOrderScenario();

    $first = purchaseOrderPayload($supplier, $warehouse, $item, $unit);
    $second = purchaseOrderPayload($supplier, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('purchase-orders.store', ['company' => $company->id]), $first);
    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('purchase-orders.store', ['company' => $company->id]), $second);

    expect(PurchaseOrder::find($first['id'])->code)->toBe('OCO000001');
    expect(PurchaseOrder::find($second['id'])->code)->toBe('OCO000002');
});

test('the order requires at least one line', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseOrderScenario();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('purchase-orders.store', ['company' => $company->id]), purchaseOrderPayload(
            $supplier,
            $warehouse,
            $item,
            $unit,
            ['lines' => []],
        ))
        ->assertSessionHasErrors('lines');
});

test('the supplier and the warehouse are required', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseOrderScenario();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('purchase-orders.store', ['company' => $company->id]), purchaseOrderPayload(
            $supplier,
            $warehouse,
            $item,
            $unit,
            ['supplier_id' => '', 'warehouse_id' => ''],
        ))
        ->assertSessionHasErrors(['supplier_id', 'warehouse_id']);
});

test('a supplier from another company is rejected', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseOrderScenario();

    $foreign = Supplier::factory()->create();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('purchase-orders.store', ['company' => $company->id]), purchaseOrderPayload(
            $supplier,
            $warehouse,
            $item,
            $unit,
            ['supplier_id' => $foreign->id],
        ))
        ->assertSessionHasErrors('supplier_id');
});

test('a warehouse from another company is rejected', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseOrderScenario();

    $foreign = Warehouse::factory()->create();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('purchase-orders.store', ['company' => $company->id]), purchaseOrderPayload(
            $supplier,
            $warehouse,
            $item,
            $unit,
            ['warehouse_id' => $foreign->id],
        ))
        ->assertSessionHasErrors('warehouse_id');
});

test('the line quantity must be greater than zero', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseOrderScenario();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('purchase-orders.store', ['company' => $company->id]), purchaseOrderPayload(
            $supplier,
            $warehouse,
            $item,
            $unit,
            ['lines' => [[
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 0,
                'unit_price' => 10,
            ]]],
        ))
        ->assertSessionHasErrors('lines.0.quantity');
});

test('the line unit must belong to the item', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseOrderScenario();

    $strayUnit = MeasurementUnit::factory()->create(['company_id' => $company->id]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('purchase-orders.store', ['company' => $company->id]), purchaseOrderPayload(
            $supplier,
            $warehouse,
            $item,
            $unit,
            ['lines' => [[
                'item_id' => $item->id,
                'measurement_unit_id' => $strayUnit->id,
                'quantity' => 1,
                'unit_price' => 10,
            ]]],
        ))
        ->assertSessionHasErrors('lines.0.measurement_unit_id');
});

test('an item from another company is rejected', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseOrderScenario();

    $foreign = Item::factory()->create();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('purchase-orders.store', ['company' => $company->id]), purchaseOrderPayload(
            $supplier,
            $warehouse,
            $item,
            $unit,
            ['lines' => [[
                'item_id' => $foreign->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 1,
                'unit_price' => 10,
            ]]],
        ))
        ->assertSessionHasErrors('lines.0.item_id');
});

test('the expected date cannot be earlier than the order date', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseOrderScenario();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('purchase-orders.store', ['company' => $company->id]), purchaseOrderPayload(
            $supplier,
            $warehouse,
            $item,
            $unit,
            ['expected_date' => now()->subDay()->toDateString()],
        ))
        ->assertSessionHasErrors('expected_date');
});

test('the global discount cannot exceed the subtotal of the lines', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseOrderScenario();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('purchase-orders.store', ['company' => $company->id]), purchaseOrderPayload(
            $supplier,
            $warehouse,
            $item,
            $unit,
            ['discount_amount' => 1000],
        ))
        ->assertSessionHasErrors('discount_amount');
});

test('a user without permission cannot create a purchase order', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseOrderScenario();
    assignRoleWithPermissions($user, $company, ['purchase-orders.list']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('purchase-orders.store', ['company' => $company->id]),
            purchaseOrderPayload($supplier, $warehouse, $item, $unit),
        )
        ->assertForbidden();
});
