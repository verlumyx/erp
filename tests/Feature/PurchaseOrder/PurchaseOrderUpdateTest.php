<?php

declare(strict_types=1);

use App\Modules\PurchaseOrder\Models\PurchaseOrder;

use function Pest\Laravel\actingAs;

/**
 * Crea una orden por HTTP y devuelve el modelo recién persistido, que es la
 * única forma de tener líneas con ids reales para luego editarlas.
 */
function createPurchaseOrder(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    array $payload,
): PurchaseOrder {
    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('purchase-orders.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    return PurchaseOrder::with('lines')->findOrFail($payload['id']);
}

test('a draft purchase order can be updated', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseOrderScenario();

    $payload = purchaseOrderPayload($supplier, $warehouse, $item, $unit);
    $order = createPurchaseOrder($user, $company, $payload);
    $line = $order->lines->first();

    $response = actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-orders.update', ['company' => $company->id, 'id' => $order->id]), [
            ...$payload,
            'supplier_reference' => 'COT-999',
            'lines' => [[
                'id' => $line->id,
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 4,
                'unit_price' => 25,
            ]],
        ]);

    $response->assertRedirect(route('purchase-orders.show', ['company' => $company->id, 'id' => $order->id]));
    $response->assertSessionHasNoErrors();

    $order->refresh()->load('lines');
    expect($order->supplier_reference)->toBe('COT-999');
    expect($order->lines)->toHaveCount(1);
    expect((float) $order->lines->first()->quantity)->toBe(4.0);
    expect((float) $order->subtotal)->toBe(100.0);
    expect((float) $order->total)->toBe(100.0);
});

test('a line that stops being sent is deactivated, never deleted', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseOrderScenario();

    $payload = purchaseOrderPayload($supplier, $warehouse, $item, $unit, [
        'lines' => [
            ['item_id' => $item->id, 'measurement_unit_id' => $unit->id, 'quantity' => 1, 'unit_price' => 10],
            ['item_id' => $item->id, 'measurement_unit_id' => $unit->id, 'quantity' => 2, 'unit_price' => 20],
        ],
    ]);

    $order = createPurchaseOrder($user, $company, $payload);
    $kept = $order->lines->firstWhere('line_number', 1);
    $dropped = $order->lines->firstWhere('line_number', 2);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-orders.update', ['company' => $company->id, 'id' => $order->id]), [
            ...$payload,
            'lines' => [[
                'id' => $kept->id,
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 1,
                'unit_price' => 10,
            ]],
        ])
        ->assertSessionHasNoErrors();

    expect($dropped->refresh()->status)->toBe('inactive');
    expect($kept->refresh()->status)->toBe('active');

    // Los totales suman solo las líneas activas.
    expect((float) $order->refresh()->subtotal)->toBe(10.0);
});

test('a new line takes the next free number and does not reuse the one dropped', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseOrderScenario();

    $payload = purchaseOrderPayload($supplier, $warehouse, $item, $unit, [
        'lines' => [
            ['item_id' => $item->id, 'measurement_unit_id' => $unit->id, 'quantity' => 1, 'unit_price' => 10],
            ['item_id' => $item->id, 'measurement_unit_id' => $unit->id, 'quantity' => 2, 'unit_price' => 20],
        ],
    ]);

    $order = createPurchaseOrder($user, $company, $payload);
    $kept = $order->lines->firstWhere('line_number', 2);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-orders.update', ['company' => $company->id, 'id' => $order->id]), [
            ...$payload,
            'lines' => [
                [
                    'id' => $kept->id,
                    'item_id' => $item->id,
                    'measurement_unit_id' => $unit->id,
                    'quantity' => 2,
                    'unit_price' => 20,
                ],
                ['item_id' => $item->id, 'measurement_unit_id' => $unit->id, 'quantity' => 3, 'unit_price' => 30],
            ],
        ])
        ->assertSessionHasNoErrors();

    $numbers = $order->refresh()->load('lines')->lines
        ->where('status', 'active')
        ->pluck('line_number')
        ->sort()
        ->values()
        ->all();

    expect($numbers)->toBe([2, 3]);
});

test('a confirmed purchase order can no longer be edited', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseOrderScenario();

    $payload = purchaseOrderPayload($supplier, $warehouse, $item, $unit);
    $order = createPurchaseOrder($user, $company, $payload);
    $order->update(['status' => 'confirmed']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(
            route('purchase-orders.update', ['company' => $company->id, 'id' => $order->id]),
            [...$payload, 'supplier_reference' => 'COT-999'],
        )
        ->assertSessionHasErrors('status');

    expect($order->refresh()->supplier_reference)->not->toBe('COT-999');
});

test('an order from another company cannot be updated', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseOrderScenario();

    $payload = purchaseOrderPayload($supplier, $warehouse, $item, $unit);
    $order = createPurchaseOrder($user, $company, $payload);

    $foreign = PurchaseOrder::factory()->create();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-orders.update', ['company' => $company->id, 'id' => $foreign->id]), $payload)
        ->assertNotFound();
});

test('a user without permission cannot update a purchase order', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseOrderScenario();

    $payload = purchaseOrderPayload($supplier, $warehouse, $item, $unit);
    $order = createPurchaseOrder($user, $company, $payload);

    assignRoleWithPermissions($user, $company, ['purchase-orders.list']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-orders.update', ['company' => $company->id, 'id' => $order->id]), $payload)
        ->assertForbidden();
});
