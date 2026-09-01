<?php

declare(strict_types=1);

use App\Modules\Adjustment\Models\Adjustment;
use App\Modules\Role\Models\Role;
use App\Modules\User\Models\User;
use Illuminate\Support\Str;

/** Un segundo usuario de la misma empresa, con acceso total. */
function secondApprover(
    \App\Modules\Company\Models\Company $company,
): User {
    $approver = User::factory()->create();

    $role = Role::create([
        'company_id' => $company->id,
        'name' => 'Aprobador '.uniqid(),
        'status' => 'active',
        'permission_type' => 'all',
    ]);

    $approver->companies()->attach($company->id, [
        'id' => (string) Str::uuid(),
        'role_id' => $role->id,
        'status' => 'active',
    ]);

    return $approver;
}

test('above the threshold the creator cannot approve their own adjustment', function () {
    [$user, $company, $warehouse, $location, $item, $unit] = adjustmentScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, [
        'quantity' => 10,
        'unitCost' => 25,
    ]);

    /** El sobrante vale 50 y el umbral es 10: hace falta otra firma. */
    setAdjustmentThreshold($company, 10);

    $adjustment = createAdjustment($user, $company, $warehouse, $item, $unit);

    moveAdjustmentTo($user, $company, $adjustment, 'pending_approval')
        ->assertSessionHasNoErrors();

    moveAdjustmentTo($user, $company, $adjustment->refresh(), 'confirmed')
        ->assertSessionHasErrors('status');

    expect(Adjustment::find($adjustment->id)->status)->toBe('pending_approval');
    expect(adjustmentMovements($adjustment))->toHaveCount(0);
});

test('above the threshold somebody else can approve it', function () {
    [$user, $company, $warehouse, $location, $item, $unit] = adjustmentScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, [
        'quantity' => 10,
        'unitCost' => 25,
    ]);

    setAdjustmentThreshold($company, 10);

    $adjustment = createAdjustment($user, $company, $warehouse, $item, $unit);

    moveAdjustmentTo($user, $company, $adjustment, 'pending_approval')
        ->assertSessionHasNoErrors();

    $approver = secondApprover($company);

    moveAdjustmentTo($approver, $company, $adjustment->refresh(), 'confirmed')
        ->assertSessionHasNoErrors();

    $adjustment->refresh();
    expect($adjustment->status)->toBe('confirmed');
    expect($adjustment->approved_by)->toBe($approver->id);
    expect(warehouseBalance($company, $item, $warehouse))->toBe(12.0);
});

test('below the threshold the same person can register and apply it', function () {
    [$user, $company, $warehouse, $location, $item, $unit] = adjustmentScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, [
        'quantity' => 10,
        'unitCost' => 25,
    ]);

    /** El sobrante vale 50 y el umbral es 500: una firma basta. */
    setAdjustmentThreshold($company, 500);

    $adjustment = createAdjustment($user, $company, $warehouse, $item, $unit);

    applyAdjustment($user, $company, $adjustment)->assertSessionHasNoErrors();

    expect(Adjustment::find($adjustment->id)->approved_by)->toBe($user->id);
});

test('the threshold looks at the size of the impact, not at its sign', function () {
    [$user, $company, $warehouse, $location, $item, $unit] = adjustmentScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, [
        'quantity' => 10,
        'unitCost' => 25,
    ]);

    setAdjustmentThreshold($company, 10);

    /** Un faltante de 3 unidades: −75, que en tamaño pasa del umbral igual. */
    $adjustment = createAdjustment($user, $company, $warehouse, $item, $unit, [
        'type' => 'loss',
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'counted_quantity' => 7,
        ]],
    ]);

    moveAdjustmentTo($user, $company, $adjustment, 'pending_approval')
        ->assertSessionHasNoErrors();

    moveAdjustmentTo($user, $company, $adjustment->refresh(), 'confirmed')
        ->assertSessionHasErrors('status');

    expect(warehouseBalance($company, $item, $warehouse))->toBe(10.0);
});

test('without the approve permission the adjustment cannot be applied', function () {
    [$user, $company, $warehouse, $location, $item, $unit] = adjustmentScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, [
        'quantity' => 10,
        'unitCost' => 25,
    ]);

    $adjustment = createAdjustment($user, $company, $warehouse, $item, $unit);

    moveAdjustmentTo($user, $company, $adjustment, 'pending_approval')
        ->assertSessionHasNoErrors();

    /** Puede mover el documento, pero no firmar que el inventario cambie. */
    restrictPermissions($user, $company, [
        'adjustments.list',
        'adjustments.show',
        'adjustments.update-status',
    ]);

    moveAdjustmentTo($user, $company, $adjustment->refresh(), 'confirmed')
        ->assertSessionHasErrors('status');

    expect(Adjustment::find($adjustment->id)->status)->toBe('pending_approval');
    expect(adjustmentMovements($adjustment))->toHaveCount(0);
});

test('if the stock moved since the count the adjustment asks for a recount', function () {
    [$user, $company, $warehouse, $location, $item, $unit] = adjustmentScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, [
        'quantity' => 10,
        'unitCost' => 25,
    ]);

    $adjustment = createAdjustment($user, $company, $warehouse, $item, $unit);

    moveAdjustmentTo($user, $company, $adjustment, 'pending_approval')
        ->assertSessionHasNoErrors();

    /** La bodega siguió trabajando entre el conteo y la aprobación. */
    registerInventoryMovement($company, $item, $warehouse, $location, [
        'quantity' => 4,
        'unitCost' => 25,
    ]);

    moveAdjustmentTo($user, $company, $adjustment->refresh(), 'confirmed')
        ->assertSessionHasErrors('status');

    expect(Adjustment::find($adjustment->id)->status)->toBe('pending_approval');
    expect(adjustmentMovements($adjustment))->toHaveCount(0);
    expect(warehouseBalance($company, $item, $warehouse))->toBe(14.0);
});
