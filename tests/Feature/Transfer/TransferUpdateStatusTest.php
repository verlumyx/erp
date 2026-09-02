<?php

declare(strict_types=1);

use App\Modules\Dispatch\Models\DispatchLine;
use App\Modules\Item\Models\Item;
use App\Modules\Transfer\Models\Transfer;
use App\Modules\Transfer\Models\TransferLine;

use function Pest\Laravel\actingAs;

test('confirming a transfer writes the dispatch that will take the goods out', function () {
    [$user, $company, $origin, $originLocation, $destination, , $item, $unit] = transferScenario();

    stockWarehouse($company, $item, $origin, $originLocation, 20, 10);

    $transfer = createTransfer($user, $company, $origin, $destination, $item, $unit);

    /** En borrador el traslado no ha generado nada. */
    expect(transferDispatch($transfer))->toBeNull();

    moveTransferTo($user, $company, $transfer, 'confirmed')->assertSessionHasNoErrors();

    $dispatch = transferDispatch($transfer);

    expect($dispatch)->not->toBeNull();
    /** Nace en borrador: confirmar el traslado no saca nada de la bodega. */
    expect($dispatch->status)->toBe('draft');
    expect($dispatch->warehouse_id)->toBe($origin->id);
    /** Va a otra bodega propia, no a un cliente. */
    expect($dispatch->recipient_type)->toBe('warehouse');
    expect($dispatch->recipient_id)->toBe($destination->id);

    expect($dispatch->lines)->toHaveCount(1);

    $line = $dispatch->lines->first();
    expect($line->item_id)->toBe($item->id);
    expect((float) $line->quantity)->toBe(2.0);
    expect($line->sourceable_type)->toBe(TransferLine::MORPH_ALIAS);
    expect($line->sourceable_id)->toBe($transfer->lines->first()->id);

    /** Y el traslado por sí solo no tocó el kardex. */
    expect(transferMovements($transfer))->toHaveCount(0);
});

test('a line of an item that carries no stock never reaches the dispatch', function () {
    [$user, $company, $origin, $originLocation, $destination, , $item, $unit] = transferScenario();

    stockWarehouse($company, $item, $origin, $originLocation, 20, 10);

    $service = Item::factory()->create(['company_id' => $company->id, 'type' => 'service']);

    \App\Modules\Item\Models\ItemUnit::factory()->base()->create([
        'company_id' => $company->id,
        'item_id' => $service->id,
        'measurement_unit_id' => $unit->id,
    ]);

    $transfer = createTransfer($user, $company, $origin, $destination, $item, $unit, [
        'lines' => [
            ['item_id' => $item->id, 'measurement_unit_id' => $unit->id, 'quantity' => 2],
            ['item_id' => $service->id, 'measurement_unit_id' => $unit->id, 'quantity' => 1],
        ],
    ]);

    moveTransferTo($user, $company, $transfer, 'confirmed')->assertSessionHasNoErrors();

    $dispatch = transferDispatch($transfer);

    expect($dispatch->lines)->toHaveCount(1);
    expect($dispatch->lines->first()->item_id)->toBe($item->id);
});

test('the chain moves the goods from origin to destination as a transfer', function () {
    [$user, $company, $origin, $originLocation, $destination, $destinationLocation, $item, $unit] = transferScenario();

    stockWarehouse($company, $item, $origin, $originLocation, 20, 10);

    $transfer = createTransfer($user, $company, $origin, $destination, $item, $unit);

    moveTransferTo($user, $company, $transfer, 'confirmed')->assertSessionHasNoErrors();

    $dispatch = transferDispatch($transfer);

    /** Confirmar el despacho es lo que saca la mercancía del origen. */
    moveDispatchTo($user, $company, $dispatch, 'confirmed')->assertSessionHasNoErrors();

    expect($transfer->refresh()->transfer_status)->toBe('in_transit');

    $entry = dispatchEntry($dispatch->refresh());

    expect($entry)->not->toBeNull();
    expect($entry->status)->toBe('draft');
    expect($entry->entry_type)->toBe('transfer');
    expect($entry->warehouse_id)->toBe($destination->id);
    /** La mercancía ya era de la empresa: no hay proveedor detrás. */
    expect($entry->supplier_id)->toBeNull();

    /** La entrada cuelga del traslado: es lo que originó el movimiento. */
    expect($entry->sourceable_type)->toBe(Transfer::MORPH_ALIAS);
    expect($entry->sourceable_id)->toBe($transfer->id);
    expect($entry->sourceable)->toBeInstanceOf(Transfer::class);
    /** Y el despacho que la trajo sigue trazado línea a línea. */
    expect($entry->lines->first()->sourceable_type)->toBe(DispatchLine::MORPH_ALIAS);
    expect($entry->lines->first()->sourceable_id)->toBe($dispatch->lines->first()->id);

    /** El traslado los ve a los dos colgando de él. */
    expect($transfer->dispatches()->pluck('id')->all())->toBe([$dispatch->id]);
    expect($transfer->entries()->pluck('id')->all())->toBe([$entry->id]);

    /** Y confirmar la entrada es lo que la mete en el destino. */
    moveEntryTo($user, $company, $entry, 'confirmed')->assertSessionHasNoErrors();

    $movements = transferMovements($transfer->refresh());

    expect($movements)->toHaveCount(2);
    /** El kardex sigue diciendo que esto fue un traslado, no una venta y una compra. */
    expect($movements->pluck('type')->all())->toBe(['transfer_out', 'transfer_in']);

    expect((float) stockAt($item, $originLocation)->quantity)->toBe(18.0);
    expect((float) stockAt($item, $destinationLocation)->quantity)->toBe(2.0);

    /** Llegar cierra el traslado. */
    expect($transfer->refresh()->transfer_status)->toBe('received');
    expect($transfer->status)->toBe('completed');
    expect($transfer->received_date)->not->toBeNull();
});

test('the cost travels with the goods: the destination receives at the cost of the origin', function () {
    [$user, $company, $origin, $originLocation, $destination, $destinationLocation, $item, $unit] = transferScenario();

    /** Veinte unidades a 10: el origen vale 10 por unidad. */
    stockWarehouse($company, $item, $origin, $originLocation, 20, 10);

    $transfer = createTransfer($user, $company, $origin, $destination, $item, $unit);

    moveTransferTo($user, $company, $transfer, 'confirmed')->assertSessionHasNoErrors();

    $dispatch = transferDispatch($transfer);
    moveDispatchTo($user, $company, $dispatch, 'confirmed')->assertSessionHasNoErrors();

    /** El costo real de la salida se congela en la línea del traslado. */
    expect((float) $transfer->refresh()->lines->first()->unit_cost)->toBe(10.0);

    $entry = dispatchEntry($dispatch->refresh());
    moveEntryTo($user, $company, $entry, 'confirmed')->assertSessionHasNoErrors();

    $movements = transferMovements($transfer);

    /** Entra al costo con el que salió, no al promedio del destino. */
    expect((float) $movements->last()->unit_cost)->toBe(10.0);
    expect((float) stockAt($item, $destinationLocation)->quantity)->toBe(2.0);
});

test('confirming the dispatch without stock in the origin is rejected', function () {
    [$user, $company, $origin, , $destination, , $item, $unit] = transferScenario();

    $transfer = createTransfer($user, $company, $origin, $destination, $item, $unit);

    moveTransferTo($user, $company, $transfer, 'confirmed')->assertSessionHasNoErrors();

    $dispatch = transferDispatch($transfer);

    moveDispatchTo($user, $company, $dispatch, 'confirmed')->assertSessionHasErrors('status');

    expect($dispatch->refresh()->status)->toBe('draft');
    expect(transferMovements($transfer))->toHaveCount(0);
});

test('cancelling the transfer cancels the dispatch it had generated', function () {
    [$user, $company, $origin, $originLocation, $destination, , $item, $unit] = transferScenario();

    stockWarehouse($company, $item, $origin, $originLocation, 20, 10);

    $transfer = createTransfer($user, $company, $origin, $destination, $item, $unit);

    moveTransferTo($user, $company, $transfer, 'confirmed')->assertSessionHasNoErrors();

    $dispatch = transferDispatch($transfer);

    moveTransferTo($user, $company, $transfer, 'cancelled')->assertSessionHasNoErrors();

    expect($dispatch->refresh()->status)->toBe('cancelled');
    expect($transfer->refresh()->status)->toBe('cancelled');
});

test('a transfer whose dispatch already left cannot be cancelled', function () {
    [$user, $company, $origin, $originLocation, $destination, , $item, $unit] = transferScenario();

    stockWarehouse($company, $item, $origin, $originLocation, 20, 10);

    $transfer = createTransfer($user, $company, $origin, $destination, $item, $unit);

    moveTransferTo($user, $company, $transfer, 'confirmed')->assertSessionHasNoErrors();

    $dispatch = transferDispatch($transfer);
    moveDispatchTo($user, $company, $dispatch, 'confirmed')->assertSessionHasNoErrors();

    /** La mercancía ya salió: primero se anula el despacho que la sacó. */
    moveTransferTo($user, $company, $transfer, 'cancelled')->assertSessionHasErrors('status');

    expect($transfer->refresh()->status)->toBe('confirmed');
    expect((float) stockAt($item, $originLocation)->quantity)->toBe(18.0);
});

test('a dispatch whose entry already arrived cannot be cancelled', function () {
    [$user, $company, $origin, $originLocation, $destination, , $item, $unit] = transferScenario();

    stockWarehouse($company, $item, $origin, $originLocation, 20, 10);

    $transfer = createTransfer($user, $company, $origin, $destination, $item, $unit);

    moveTransferTo($user, $company, $transfer, 'confirmed')->assertSessionHasNoErrors();

    $dispatch = transferDispatch($transfer);
    moveDispatchTo($user, $company, $dispatch, 'confirmed')->assertSessionHasNoErrors();

    $entry = dispatchEntry($dispatch->refresh());
    moveEntryTo($user, $company, $entry, 'confirmed')->assertSessionHasNoErrors();

    moveDispatchTo($user, $company, $dispatch, 'cancelled')->assertSessionHasErrors('status');

    expect($dispatch->refresh()->status)->toBe('confirmed');
});

test('cancelling a draft reverses nothing', function () {
    [$user, $company, $origin, , $destination, , $item, $unit] = transferScenario();

    $transfer = createTransfer($user, $company, $origin, $destination, $item, $unit);

    moveTransferTo($user, $company, $transfer, 'cancelled')->assertSessionHasNoErrors();

    expect($transfer->refresh()->status)->toBe('cancelled');
    expect(transferMovements($transfer))->toHaveCount(0);
});

test('the transfer is not closed by hand: the entry closes it', function () {
    [$user, $company, $origin, $originLocation, $destination, , $item, $unit] = transferScenario();

    stockWarehouse($company, $item, $origin, $originLocation, 20, 10);

    $transfer = createTransfer($user, $company, $origin, $destination, $item, $unit);

    moveTransferTo($user, $company, $transfer, 'confirmed')->assertSessionHasNoErrors();

    moveTransferTo($user, $company, $transfer, 'completed')->assertSessionHasErrors('status');

    expect($transfer->refresh()->status)->toBe('confirmed');
});

test('a draft cannot jump straight to completed', function () {
    [$user, $company, $origin, , $destination, , $item, $unit] = transferScenario();

    $transfer = createTransfer($user, $company, $origin, $destination, $item, $unit);

    moveTransferTo($user, $company, $transfer, 'completed')->assertSessionHasErrors('status');

    expect($transfer->refresh()->status)->toBe('draft');
});

test('changing the status needs its permission', function () {
    [$user, $company, $origin, , $destination, , $item, $unit] = transferScenario();

    $transfer = createTransfer($user, $company, $origin, $destination, $item, $unit);

    restrictPermissions($user, $company, ['transfers.list']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('transfers.update-status', ['company' => $company->id, 'id' => $transfer->id]),
            ['status' => 'confirmed'],
        )
        ->assertForbidden();
});
