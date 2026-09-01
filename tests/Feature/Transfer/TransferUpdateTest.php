<?php

declare(strict_types=1);

use App\Modules\Transfer\Models\TransferLine;

use function Pest\Laravel\actingAs;

/**
 * @param  array<string, mixed>  $overrides
 */
function updateTransfer(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    \App\Modules\Transfer\Models\Transfer $transfer,
    \App\Modules\Warehouse\Models\Warehouse $origin,
    \App\Modules\Warehouse\Models\Warehouse $destination,
    \App\Modules\Item\Models\Item $item,
    \App\Modules\MeasurementUnit\Models\MeasurementUnit $unit,
    array $overrides = [],
): \Illuminate\Testing\TestResponse {
    $payload = transferPayload($origin, $destination, $item, $unit, $overrides);
    unset($payload['id']);

    return actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('transfers.update', ['company' => $company->id, 'id' => $transfer->id]), $payload);
}

test('a draft can be edited', function () {
    [$user, $company, $origin, , $destination, , $item, $unit] = transferScenario();

    $transfer = createTransfer($user, $company, $origin, $destination, $item, $unit);

    updateTransfer($user, $company, $transfer, $origin, $destination, $item, $unit, [
        'reason' => 'rebalance',
        'vehicle_plate' => 'AB123CD',
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 7,
        ]],
    ])->assertSessionHasNoErrors();

    $transfer->refresh();
    expect($transfer->reason)->toBe('rebalance');
    expect($transfer->vehicle_plate)->toBe('AB123CD');
    expect((float) $transfer->total_quantity)->toBe(7.0);
});

test('a line that stops coming is deactivated, never deleted', function () {
    [$user, $company, $origin, , $destination, , $item, $unit] = transferScenario();

    $transfer = createTransfer($user, $company, $origin, $destination, $item, $unit, [
        'lines' => [
            ['item_id' => $item->id, 'measurement_unit_id' => $unit->id, 'quantity' => 2],
            ['item_id' => $item->id, 'measurement_unit_id' => $unit->id, 'quantity' => 3],
        ],
    ]);

    $kept = $transfer->lines->firstWhere('line_number', 1);

    updateTransfer($user, $company, $transfer, $origin, $destination, $item, $unit, [
        'lines' => [[
            'id' => $kept->id,
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 2,
        ]],
    ])->assertSessionHasNoErrors();

    /** Las dos filas siguen ahí: la que salió del formulario quedó inactiva. */
    expect(TransferLine::where('transfer_id', $transfer->id)->count())->toBe(2);
    expect(TransferLine::where('transfer_id', $transfer->id)->where('status', 'active')->count())->toBe(1);
    /** Y los totales solo suman las activas. */
    expect((float) $transfer->refresh()->total_quantity)->toBe(2.0);
});

test('a confirmed transfer can no longer be edited', function () {
    [$user, $company, $origin, $originLocation, $destination, , $item, $unit] = transferScenario();

    registerInventoryMovement($company, $item, $origin, $originLocation, ['quantity' => 10, 'unitCost' => 30]);

    $transfer = createTransfer($user, $company, $origin, $destination, $item, $unit);
    moveTransferTo($user, $company, $transfer, 'confirmed')->assertSessionHasNoErrors();

    updateTransfer($user, $company, $transfer->refresh(), $origin, $destination, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 9,
        ]],
    ])->assertSessionHasErrors('status');

    expect((float) $transfer->refresh()->total_quantity)->toBe(2.0);
});

test('editing needs the update permission', function () {
    [$user, $company, $origin, , $destination, , $item, $unit] = transferScenario();

    $transfer = createTransfer($user, $company, $origin, $destination, $item, $unit);

    restrictPermissions($user, $company, ['transfers.list']);

    updateTransfer($user, $company, $transfer, $origin, $destination, $item, $unit)
        ->assertForbidden();
});
