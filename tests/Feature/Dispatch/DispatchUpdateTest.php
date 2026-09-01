<?php

declare(strict_types=1);

use App\Modules\Dispatch\Models\Dispatch;
use App\Modules\Dispatch\Models\DispatchLine;

use function Pest\Laravel\actingAs;

/**
 * @param  array<string, mixed>  $overrides
 */
function updateDispatch(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    Dispatch $dispatch,
    array $payload,
): \Illuminate\Testing\TestResponse {
    return actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('dispatches.update', ['company' => $company->id, 'id' => $dispatch->id]),
            $payload,
        );
}

test('a draft dispatch can be edited', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = dispatchScenario();

    $dispatch = createDispatch($user, $company, $client, $warehouse, $item, $unit);

    $payload = dispatchPayload($client, $warehouse, $item, $unit, [
        'carrier' => 'Otro transportista',
        'lines' => [
            [
                'id' => $dispatch->lines->first()->id,
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 5,
                'unit_price' => 120,
            ],
        ],
    ]);
    unset($payload['id']);

    updateDispatch($user, $company, $dispatch, $payload)
        ->assertRedirect(route('dispatches.show', ['company' => $company->id, 'id' => $dispatch->id]))
        ->assertSessionHasNoErrors();

    $dispatch->refresh()->load('lines');
    expect($dispatch->carrier)->toBe('Otro transportista');
    expect($dispatch->lines)->toHaveCount(1);
    expect((float) $dispatch->lines->first()->quantity)->toBe(5.0);
    /** El número de la línea no se reinventa al editarla. */
    expect($dispatch->lines->first()->line_number)->toBe(1);
    expect((float) $dispatch->total_quantity)->toBe(5.0);
});

test('a line that stops coming is deactivated, never deleted', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = dispatchScenario();

    $dispatch = createDispatch($user, $company, $client, $warehouse, $item, $unit, [
        'lines' => [
            ['item_id' => $item->id, 'measurement_unit_id' => $unit->id, 'quantity' => 1, 'unit_price' => 10],
            ['item_id' => $item->id, 'measurement_unit_id' => $unit->id, 'quantity' => 2, 'unit_price' => 20],
        ],
    ]);

    $kept = $dispatch->lines->firstWhere('line_number', 1);
    $dropped = $dispatch->lines->firstWhere('line_number', 2);

    $payload = dispatchPayload($client, $warehouse, $item, $unit, [
        'lines' => [
            [
                'id' => $kept->id,
                'item_id' => $item->id,
                'measurement_unit_id' => $unit->id,
                'quantity' => 1,
                'unit_price' => 10,
            ],
        ],
    ]);
    unset($payload['id']);

    updateDispatch($user, $company, $dispatch, $payload)->assertSessionHasNoErrors();

    expect(DispatchLine::find($dropped->id)->status)->toBe('inactive');
    /** Y los totales solo cuentan lo activo. */
    expect((float) $dispatch->refresh()->total_quantity)->toBe(1.0);
});

test('a confirmed dispatch is no longer edited', function () {
    [$user, $company, $client, $warehouse, $item, $unit, $location] = dispatchScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, ['quantity' => 10, 'unitCost' => 5]);

    $dispatch = createDispatch($user, $company, $client, $warehouse, $item, $unit);
    moveDispatchTo($user, $company, $dispatch, 'confirmed')->assertSessionHasNoErrors();

    $payload = dispatchPayload($client, $warehouse, $item, $unit, ['carrier' => 'Tarde']);
    unset($payload['id']);

    updateDispatch($user, $company, $dispatch->refresh(), $payload)->assertSessionHasErrors('status');

    expect($dispatch->refresh()->carrier)->toBeNull();
});

test('editing requires the update permission', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = dispatchScenario();

    $dispatch = createDispatch($user, $company, $client, $warehouse, $item, $unit);

    assignRoleWithPermissions($user, $company, ['dispatches.list', 'dispatches.show']);

    $payload = dispatchPayload($client, $warehouse, $item, $unit);
    unset($payload['id']);

    updateDispatch($user, $company, $dispatch, $payload)->assertForbidden();
});

test('a dispatch from another company cannot be edited', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = dispatchScenario();

    $dispatch = createDispatch($user, $company, $client, $warehouse, $item, $unit);

    [$stranger, $otherCompany] = createUserWithCompany();

    actingAs($stranger)
        ->withSession(['current_company_id' => $otherCompany->id])
        ->get(route('dispatches.show', ['company' => $otherCompany->id, 'id' => $dispatch->id]))
        ->assertNotFound();
});
