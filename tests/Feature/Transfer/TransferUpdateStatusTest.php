<?php

declare(strict_types=1);

use App\Modules\Item\Models\ItemUnit;
use App\Modules\MeasurementUnit\Models\MeasurementUnit;
use App\Modules\Warehouse\Models\Warehouse;
use App\Modules\WarehouseLocation\Models\WarehouseLocation;

use function Pest\Laravel\actingAs;

test('an immediate transfer moves the goods from origin to destination in one act', function () {
    [$user, $company, $origin, $originLocation, $destination, $destinationLocation, $item, $unit] = transferScenario();

    /** La bodega de origen tiene existencia comprada a 20 y a 40: promedio 30. */
    registerInventoryMovement($company, $item, $origin, $originLocation, ['quantity' => 10, 'unitCost' => 20]);
    registerInventoryMovement($company, $item, $origin, $originLocation, ['quantity' => 10, 'unitCost' => 40]);

    $transfer = createTransfer($user, $company, $origin, $destination, $item, $unit);

    moveTransferTo($user, $company, $transfer, 'confirmed')->assertSessionHasNoErrors();

    $transfer->refresh();
    expect($transfer->status)->toBe('confirmed');
    /** Sin bodega de tránsito la mercancía llega en el mismo acto. */
    expect($transfer->transfer_status)->toBe('received');
    expect($transfer->sent_by)->toBe($user->id);

    $movements = transferMovements($transfer);
    expect($movements)->toHaveCount(2);

    [$exit, $arrival] = [$movements[0], $movements[1]];

    expect($exit->type)->toBe('transfer_out');
    expect($exit->warehouse_id)->toBe($origin->id);
    expect($exit->location_id)->toBe($originLocation->id);
    expect((float) $exit->quantity)->toBe(2.0);
    /** La salida se valora al promedio de la bodega de origen. */
    expect((float) $exit->unit_cost)->toBe(30.0);

    expect($arrival->type)->toBe('transfer_in');
    expect($arrival->warehouse_id)->toBe($destination->id);
    expect($arrival->location_id)->toBe($destinationLocation->id);
    /** El costo viaja con la mercancía: entra con el del origen. */
    expect((float) $arrival->unit_cost)->toBe(30.0);

    /** El inventario cambió de sitio, no de valor. */
    expect(warehouseBalance($company, $item, $origin))->toBe(18.0);
    expect(warehouseBalance($company, $item, $destination))->toBe(2.0);
});

test('the frozen cost is copied to the line and to the header', function () {
    [$user, $company, $origin, $originLocation, $destination, , $item, $unit] = transferScenario();

    registerInventoryMovement($company, $item, $origin, $originLocation, ['quantity' => 10, 'unitCost' => 20]);
    registerInventoryMovement($company, $item, $origin, $originLocation, ['quantity' => 10, 'unitCost' => 40]);

    $transfer = createTransfer($user, $company, $origin, $destination, $item, $unit);

    moveTransferTo($user, $company, $transfer, 'confirmed')->assertSessionHasNoErrors();

    $line = $transfer->refresh()->lines()->first();
    expect((float) $line->unit_cost)->toBe(30.0);
    expect((float) $line->sent_quantity)->toBe(2.0);
    expect((float) $transfer->total_cost)->toBe(60.0);
});

test('the movements are written in the base unit of the item', function () {
    [$user, $company, $origin, $originLocation, $destination, , $item, $unit] = transferScenario();

    $box = MeasurementUnit::factory()->create(['company_id' => $company->id]);
    ItemUnit::factory()->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
        'measurement_unit_id' => $box->id,
        'is_base' => 'no',
        'conversion_factor' => 12,
    ]);

    registerInventoryMovement($company, $item, $origin, $originLocation, ['quantity' => 100, 'unitCost' => 5]);

    $transfer = createTransfer($user, $company, $origin, $destination, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $box->id,
            'quantity' => 2,
        ]],
    ]);

    moveTransferTo($user, $company, $transfer, 'confirmed')->assertSessionHasNoErrors();

    $movements = transferMovements($transfer);
    expect((float) $movements[0]->quantity)->toBe(24.0);
    expect((float) $movements[1]->quantity)->toBe(24.0);
});

test('a two step transfer parks the goods in the transit warehouse', function () {
    [$user, $company, $origin, $originLocation, $destination, , $item, $unit] = transferScenario();

    [$transit] = warehouseWithDefaultLocation($company, $user);

    registerInventoryMovement($company, $item, $origin, $originLocation, ['quantity' => 10, 'unitCost' => 30]);

    $transfer = createTransfer($user, $company, $origin, $destination, $item, $unit, [
        'transit_warehouse_id' => $transit->id,
    ]);

    moveTransferTo($user, $company, $transfer, 'confirmed')->assertSessionHasNoErrors();

    $transfer->refresh();
    expect($transfer->transfer_status)->toBe('in_transit');
    expect($transfer->received_date)->toBeNull();

    $movements = transferMovements($transfer);
    expect($movements)->toHaveCount(2);
    expect($movements[1]->warehouse_id)->toBe($transit->id);

    /** La mercancía salió del origen pero todavía no llegó al destino. */
    expect(warehouseBalance($company, $item, $origin))->toBe(8.0);
    expect(warehouseBalance($company, $item, $transit))->toBe(2.0);
    expect(warehouseBalance($company, $item, $destination))->toBe(0.0);
});

test('confirming without stock in the origin is rejected', function () {
    [$user, $company, $origin, , $destination, , $item, $unit] = transferScenario();

    $transfer = createTransfer($user, $company, $origin, $destination, $item, $unit);

    moveTransferTo($user, $company, $transfer, 'confirmed')->assertSessionHasErrors('status');

    expect($transfer->refresh()->status)->toBe('draft');
    expect(transferMovements($transfer))->toHaveCount(0);
});

test('a warehouse without a default location blocks the confirmation', function () {
    [$user, $company, $origin, $originLocation, , , $item, $unit] = transferScenario();

    /** Una bodega sin ubicaciones no tiene sitio al que llevar la mercancía. */
    $destination = Warehouse::factory()->create([
        'company_id' => $company->id,
        'uses_locations' => 'no',
    ]);
    WarehouseLocation::where('warehouse_id', $destination->id)->update(['is_default' => 'no']);

    registerInventoryMovement($company, $item, $origin, $originLocation, ['quantity' => 10, 'unitCost' => 30]);

    $transfer = createTransfer($user, $company, $origin, $destination, $item, $unit);

    moveTransferTo($user, $company, $transfer, 'confirmed')->assertSessionHasErrors('status');

    expect($transfer->refresh()->status)->toBe('draft');
});

test('cancelling a confirmed transfer brings every movement back', function () {
    [$user, $company, $origin, $originLocation, $destination, , $item, $unit] = transferScenario();

    registerInventoryMovement($company, $item, $origin, $originLocation, ['quantity' => 10, 'unitCost' => 30]);

    $transfer = createTransfer($user, $company, $origin, $destination, $item, $unit);
    moveTransferTo($user, $company, $transfer, 'confirmed')->assertSessionHasNoErrors();

    moveTransferTo($user, $company, $transfer, 'cancelled')->assertSessionHasNoErrors();

    $transfer->refresh();
    expect($transfer->status)->toBe('cancelled');
    expect($transfer->cancelled_at)->not->toBeNull();

    /** Dos movimientos y sus dos contrapartidas: nada se borra. */
    expect(transferMovements($transfer))->toHaveCount(4);

    expect(warehouseBalance($company, $item, $origin))->toBe(10.0);
    expect(warehouseBalance($company, $item, $destination))->toBe(0.0);
});

test('cancelling a two step transfer already received unwinds it from the end', function () {
    [$user, $company, $origin, $originLocation, $destination, , $item, $unit] = transferScenario();

    [$transit] = warehouseWithDefaultLocation($company, $user);

    registerInventoryMovement($company, $item, $origin, $originLocation, ['quantity' => 10, 'unitCost' => 30]);

    $transfer = createTransfer($user, $company, $origin, $destination, $item, $unit, [
        'transit_warehouse_id' => $transit->id,
    ]);
    moveTransferTo($user, $company, $transfer, 'confirmed')->assertSessionHasNoErrors();
    registerTransferReceipt($user, $company, $transfer)->assertSessionHasNoErrors();

    moveTransferTo($user, $company, $transfer, 'cancelled')->assertSessionHasNoErrors();

    /** Cuatro movimientos vivos y sus cuatro contrapartidas. */
    expect(transferMovements($transfer))->toHaveCount(8);

    expect(warehouseBalance($company, $item, $origin))->toBe(10.0);
    expect(warehouseBalance($company, $item, $transit))->toBe(0.0);
    expect(warehouseBalance($company, $item, $destination))->toBe(0.0);
});

test('cancelling a draft reverses nothing', function () {
    [$user, $company, $origin, , $destination, , $item, $unit] = transferScenario();

    $transfer = createTransfer($user, $company, $origin, $destination, $item, $unit);

    moveTransferTo($user, $company, $transfer, 'cancelled')->assertSessionHasNoErrors();

    expect($transfer->refresh()->status)->toBe('cancelled');
    expect(transferMovements($transfer))->toHaveCount(0);
});

test('an immediate transfer can be closed right after confirming', function () {
    [$user, $company, $origin, $originLocation, $destination, , $item, $unit] = transferScenario();

    registerInventoryMovement($company, $item, $origin, $originLocation, ['quantity' => 10, 'unitCost' => 30]);

    $transfer = createTransfer($user, $company, $origin, $destination, $item, $unit);
    moveTransferTo($user, $company, $transfer, 'confirmed')->assertSessionHasNoErrors();

    moveTransferTo($user, $company, $transfer, 'completed')->assertSessionHasNoErrors();

    expect($transfer->refresh()->status)->toBe('completed');
});

test('a transfer still travelling cannot be closed', function () {
    [$user, $company, $origin, $originLocation, $destination, , $item, $unit] = transferScenario();

    [$transit] = warehouseWithDefaultLocation($company, $user);

    registerInventoryMovement($company, $item, $origin, $originLocation, ['quantity' => 10, 'unitCost' => 30]);

    $transfer = createTransfer($user, $company, $origin, $destination, $item, $unit, [
        'transit_warehouse_id' => $transit->id,
    ]);
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
