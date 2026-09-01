<?php

declare(strict_types=1);

use function Pest\Laravel\actingAs;

/**
 * Escenario del traslado en dos pasos, ya confirmado y con la mercancía en la
 * bodega de tránsito: es sobre eso sobre lo que se registra una recepción.
 *
 * @return array{
 *     0: \App\Modules\User\Models\User,
 *     1: \App\Modules\Company\Models\Company,
 *     2: \App\Modules\Warehouse\Models\Warehouse,
 *     3: \App\Modules\Warehouse\Models\Warehouse,
 *     4: \App\Modules\Warehouse\Models\Warehouse,
 *     5: \App\Modules\Item\Models\Item,
 *     6: \App\Modules\Transfer\Models\Transfer
 * }
 */
function travellingTransfer(float $quantity = 4): array
{
    [$user, $company, $origin, $originLocation, $destination, , $item, $unit] = transferScenario();

    [$transit] = warehouseWithDefaultLocation($company, $user);

    registerInventoryMovement($company, $item, $origin, $originLocation, ['quantity' => 10, 'unitCost' => 30]);

    $transfer = createTransfer($user, $company, $origin, $destination, $item, $unit, [
        'transit_warehouse_id' => $transit->id,
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => $quantity,
        ]],
    ]);

    moveTransferTo($user, $company, $transfer, 'confirmed')->assertSessionHasNoErrors();

    return [$user, $company, $origin, $transit, $destination, $item, $transfer->refresh()];
}

test('receiving everything empties the transit warehouse into the destination', function () {
    [$user, $company, $origin, $transit, $destination, $item, $transfer] = travellingTransfer();

    registerTransferReceipt($user, $company, $transfer)->assertSessionHasNoErrors();

    $transfer->refresh();
    expect($transfer->transfer_status)->toBe('received');
    /** Sin faltante el documento sigue confirmado, listo para cerrarse. */
    expect($transfer->status)->toBe('confirmed');
    expect($transfer->received_by)->toBe($user->id);
    expect($transfer->received_date?->toDateString())->toBe(now()->toDateString());

    $line = $transfer->lines()->first();
    expect((float) $line->received_quantity)->toBe(4.0);
    expect((float) $line->difference_quantity)->toBe(0.0);

    expect(warehouseBalance($company, $item, $origin))->toBe(6.0);
    expect(warehouseBalance($company, $item, $transit))->toBe(0.0);
    expect(warehouseBalance($company, $item, $destination))->toBe(4.0);
});

test('the goods arrive at the destination with the cost they left with', function () {
    [$user, $company, , , $destination, , $transfer] = travellingTransfer();

    registerTransferReceipt($user, $company, $transfer)->assertSessionHasNoErrors();

    $arrival = transferMovements($transfer)->last();

    expect($arrival->type)->toBe('transfer_in');
    expect($arrival->warehouse_id)->toBe($destination->id);
    /** El destino no la revaloriza a su propio promedio. */
    expect((float) $arrival->unit_cost)->toBe(30.0);
});

test('receiving less leaves the difference in transit and the document half done', function () {
    [$user, $company, $origin, $transit, $destination, $item, $transfer] = travellingTransfer();

    $line = $transfer->lines()->first();

    registerTransferReceipt($user, $company, $transfer, [
        'lines' => [['id' => $line->id, 'received_quantity' => 3]],
    ])->assertSessionHasNoErrors();

    $transfer->refresh();
    expect($transfer->transfer_status)->toBe('partial_received');
    expect($transfer->status)->toBe('partial');

    $line->refresh();
    expect((float) $line->received_quantity)->toBe(3.0);
    expect((float) $line->difference_quantity)->toBe(1.0);

    /** Lo que no llegó no se pierde: se queda en tránsito esperando un ajuste. */
    expect(warehouseBalance($company, $item, $origin))->toBe(6.0);
    expect(warehouseBalance($company, $item, $transit))->toBe(1.0);
    expect(warehouseBalance($company, $item, $destination))->toBe(3.0);
});

test('a transfer cannot receive more than what left', function () {
    [$user, $company, , , , , $transfer] = travellingTransfer();

    $line = $transfer->lines()->first();

    registerTransferReceipt($user, $company, $transfer, [
        'lines' => [['id' => $line->id, 'received_quantity' => 5]],
    ])->assertSessionHasErrors('lines.0.received_quantity');

    expect($transfer->refresh()->transfer_status)->toBe('in_transit');
});

test('the receipt is registered only once', function () {
    [$user, $company, , , , , $transfer] = travellingTransfer();

    registerTransferReceipt($user, $company, $transfer)->assertSessionHasNoErrors();
    registerTransferReceipt($user, $company, $transfer->refresh())->assertSessionHasErrors('lines');
});

test('an immediate transfer has no receipt to register', function () {
    [$user, $company, $origin, $originLocation, $destination, , $item, $unit] = transferScenario();

    registerInventoryMovement($company, $item, $origin, $originLocation, ['quantity' => 10, 'unitCost' => 30]);

    $transfer = createTransfer($user, $company, $origin, $destination, $item, $unit);
    moveTransferTo($user, $company, $transfer, 'confirmed')->assertSessionHasNoErrors();

    registerTransferReceipt($user, $company, $transfer->refresh())->assertSessionHasErrors('lines');
});

test('a draft has nothing to receive', function () {
    [$user, $company, $origin, , $destination, , $item, $unit] = transferScenario();

    [$transit] = warehouseWithDefaultLocation($company, $user);

    $transfer = createTransfer($user, $company, $origin, $destination, $item, $unit, [
        'transit_warehouse_id' => $transit->id,
    ]);

    registerTransferReceipt($user, $company, $transfer)->assertSessionHasErrors('lines');
});

test('a line of another transfer is rejected', function () {
    [$user, $company, , , , , $transfer] = travellingTransfer();

    registerTransferReceipt($user, $company, $transfer, [
        'lines' => [['id' => (string) \Illuminate\Support\Str::uuid7(), 'received_quantity' => 1]],
    ])->assertSessionHasErrors('lines');
});

test('registering the receipt needs its own permission', function () {
    [$user, $company, , , , , $transfer] = travellingTransfer();

    restrictPermissions($user, $company, ['transfers.list', 'transfers.update-status']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('transfers.receipt', ['company' => $company->id, 'id' => $transfer->id]))
        ->assertForbidden();
});

test('a transfer with a difference cannot be closed without the permission', function () {
    [$user, $company, , , , , $transfer] = travellingTransfer();

    $line = $transfer->lines()->first();

    registerTransferReceipt($user, $company, $transfer, [
        'lines' => [['id' => $line->id, 'received_quantity' => 3]],
    ])->assertSessionHasNoErrors();

    restrictPermissions($user, $company, [
        'transfers.list', 'transfers.show', 'transfers.update-status',
    ]);

    moveTransferTo($user, $company, $transfer->refresh(), 'completed')
        ->assertSessionHasErrors('status');

    expect($transfer->refresh()->status)->toBe('partial');
});

test('closing a transfer with a difference is allowed to whoever may justify it', function () {
    [$user, $company, , , , , $transfer] = travellingTransfer();

    $line = $transfer->lines()->first();

    registerTransferReceipt($user, $company, $transfer, [
        'lines' => [['id' => $line->id, 'received_quantity' => 3]],
    ])->assertSessionHasNoErrors();

    moveTransferTo($user, $company, $transfer->refresh(), 'completed')->assertSessionHasNoErrors();

    expect($transfer->refresh()->status)->toBe('completed');
});
