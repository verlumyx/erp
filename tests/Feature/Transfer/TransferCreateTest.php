<?php

declare(strict_types=1);

use App\Modules\MeasurementUnit\Models\MeasurementUnit;
use App\Modules\Item\Models\ItemUnit;
use App\Modules\Transfer\Models\Transfer;
use App\Modules\Warehouse\Models\Warehouse;
use function Pest\Laravel\actingAs;

test('a transfer is created as a draft with its sequential code', function () {
    [$user, $company, $origin, , $destination, , $item, $unit] = transferScenario();

    $transfer = createTransfer($user, $company, $origin, $destination, $item, $unit);

    expect($transfer->code)->toBe('TRA000001');
    expect($transfer->status)->toBe('draft');
    /** Nada se ha movido todavía: la mercancía sigue en la bodega de origen. */
    expect($transfer->transfer_status)->toBe('pending');
    expect($transfer->origin_warehouse_id)->toBe($origin->id);
    expect($transfer->destination_warehouse_id)->toBe($destination->id);
    expect($transfer->transit_warehouse_id)->toBeNull();
    expect($transfer->lines)->toHaveCount(1);
});

test('the draft values the goods at the current average cost of the item', function () {
    [$user, $company, $origin, $originLocation, $destination, , $item, $unit] = transferScenario();

    /** La bodega tiene existencia comprada a 20 y a 40: promedio 30. */
    registerInventoryMovement($company, $item, $origin, $originLocation, ['quantity' => 10, 'unitCost' => 20]);
    registerInventoryMovement($company, $item, $origin, $originLocation, ['quantity' => 10, 'unitCost' => 40]);

    $transfer = createTransfer($user, $company, $origin, $destination, $item, $unit);

    $line = $transfer->lines->first();
    expect((float) $line->unit_cost)->toBe(30.0);
    /** El traslado no pone precio: el importe de la línea es su costo. */
    expect((float) $line->unit_price)->toBe(30.0);
    expect((float) $line->subtotal)->toBe(60.0);
    expect((float) $transfer->total_cost)->toBe(60.0);
    expect((float) $transfer->total_quantity)->toBe(2.0);
});

test('the base quantity is resolved from the unit of the line', function () {
    [$user, $company, $origin, , $destination, , $item, $unit] = transferScenario();

    $box = MeasurementUnit::factory()->create(['company_id' => $company->id]);
    ItemUnit::factory()->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
        'measurement_unit_id' => $box->id,
        'is_base' => 'no',
        'conversion_factor' => 12,
    ]);

    $transfer = createTransfer($user, $company, $origin, $destination, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $box->id,
            'quantity' => 2,
        ]],
    ]);

    expect((float) $transfer->lines->first()->base_quantity)->toBe(24.0);
});

test('the origin and the destination cannot be the same warehouse', function () {
    [$user, $company, $origin, , , , $item, $unit] = transferScenario();

    $payload = transferPayload($origin, $origin, $item, $unit);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('transfers.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('destination_warehouse_id');

    expect(Transfer::count())->toBe(0);
});

test('the transit warehouse cannot be the origin or the destination', function () {
    [$user, $company, $origin, , $destination, , $item, $unit] = transferScenario();

    $payload = transferPayload($origin, $destination, $item, $unit, [
        'transit_warehouse_id' => $destination->id,
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('transfers.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('transit_warehouse_id');
});

test('a line location has to belong to its own warehouse', function () {
    [$user, $company, $origin, , $destination, $destinationLocation, $item, $unit] = transferScenario();

    $payload = transferPayload($origin, $destination, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 2,
            /** La ubicación del destino no puede ser de dónde sale la mercancía. */
            'origin_location_id' => $destinationLocation->id,
        ]],
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('transfers.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('lines.0.origin_location_id');
});

test('a reason of other has to be explained', function () {
    [$user, $company, $origin, , $destination, , $item, $unit] = transferScenario();

    $payload = transferPayload($origin, $destination, $item, $unit, ['reason' => 'other']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('transfers.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('reason_detail');
});

test('the transfer needs at least one line', function () {
    [$user, $company, $origin, , $destination, , $item, $unit] = transferScenario();

    $payload = transferPayload($origin, $destination, $item, $unit, ['lines' => []]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('transfers.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('lines');
});

test('the unit of a line has to be one registered for the item', function () {
    [$user, $company, $origin, , $destination, , $item, $unit] = transferScenario();

    $stranger = MeasurementUnit::factory()->create(['company_id' => $company->id]);

    $payload = transferPayload($origin, $destination, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $stranger->id,
            'quantity' => 2,
        ]],
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('transfers.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('lines.0.measurement_unit_id');
});

test('creating needs the create permission', function () {
    [$user, $company, $origin, , $destination, , $item, $unit] = transferScenario();

    restrictPermissions($user, $company, ['transfers.list']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(
            route('transfers.store', ['company' => $company->id]),
            transferPayload($origin, $destination, $item, $unit),
        )
        ->assertForbidden();
});

test('codes are sequential per company', function () {
    [$user, $company, $origin, , $destination, , $item, $unit] = transferScenario();

    createTransfer($user, $company, $origin, $destination, $item, $unit);
    $second = createTransfer($user, $company, $origin, $destination, $item, $unit);

    expect($second->code)->toBe('TRA000002');
});

test('an inactive warehouse cannot be used', function () {
    [$user, $company, $origin, , $destination, , $item, $unit] = transferScenario();

    Warehouse::where('id', $destination->id)->update(['status' => 'inactive']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(
            route('transfers.store', ['company' => $company->id]),
            transferPayload($origin, $destination, $item, $unit),
        )
        ->assertSessionHasErrors('destination_warehouse_id');
});
