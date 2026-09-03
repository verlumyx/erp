<?php

declare(strict_types=1);

use App\Modules\Adjustment\Models\Adjustment;
use App\Modules\Adjustment\Models\AdjustmentLine;
use App\Modules\Adjustment\Models\AdjustmentLineLot;
use App\Modules\ItemLot\Models\ItemLot;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

test('an adjustment can be updated while it is a draft', function () {
    [$user, $company, $warehouse, $location, $item, $unit] = adjustmentScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, [
        'quantity' => 10,
        'unitCost' => 25,
    ]);

    $adjustment = createAdjustment($user, $company, $warehouse, $item, $unit);
    $line = $adjustment->lines->first();

    $payload = adjustmentPayload($warehouse, $item, $unit, [
        'reason' => 'Se volvió a contar el pasillo.',
        'lines' => [[
            'id' => $line->id,
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'counted_quantity' => 8,
        ]],
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('adjustments.update', ['company' => $company->id, 'id' => $adjustment->id]),
            $payload,
        )
        ->assertRedirect(route('adjustments.show', ['company' => $company->id, 'id' => $adjustment->id]))
        ->assertSessionHasNoErrors();

    $adjustment->refresh()->load('lines');
    expect($adjustment->reason)->toBe('Se volvió a contar el pasillo.');
    expect($adjustment->lines)->toHaveCount(1);

    $line = $adjustment->lines->first();
    /** La fila se reconoce por su id y conserva su número. */
    expect($line->line_number)->toBe(1);
    expect((float) $line->counted_quantity)->toBe(8.0);
    expect((float) $line->difference_quantity)->toBe(-2.0);
    expect($line->movement_type)->toBe(AdjustmentLine::MOVEMENT_OUT);
    expect((float) $adjustment->net_cost)->toBe(-50.0);
});

test('a line that stops coming is deactivated, never deleted', function () {
    [$user, $company, $warehouse, $location, $item, $unit] = adjustmentScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, [
        'quantity' => 10,
        'unitCost' => 25,
    ]);

    $adjustment = createAdjustment($user, $company, $warehouse, $item, $unit);
    $original = $adjustment->lines->first();

    $payload = adjustmentPayload($warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'counted_quantity' => 11,
        ]],
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('adjustments.update', ['company' => $company->id, 'id' => $adjustment->id]),
            $payload,
        )
        ->assertSessionHasNoErrors();

    expect(AdjustmentLine::where('adjustment_id', $adjustment->id)->count())->toBe(2);
    expect(AdjustmentLine::find($original->id)->status)->toBe('inactive');
    /** Los totales solo cuentan la línea viva. */
    expect((float) Adjustment::find($adjustment->id)->net_cost)->toBe(25.0);
});

test('a lot that stops coming is deactivated, never deleted', function () {
    [$user, $company, $warehouse, $location, $item, $unit] = adjustmentScenario();

    $first = ItemLot::factory()->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
    ]);

    $second = ItemLot::factory()->create([
        'company_id' => $company->id,
        'item_id' => $item->id,
    ]);

    registerInventoryMovement($company, $item, $warehouse, $location, [
        'quantity' => 10,
        'unitCost' => 25,
        'lotId' => $first->id,
    ]);

    registerInventoryMovement($company, $item, $warehouse, $location, [
        'quantity' => 4,
        'unitCost' => 25,
        'lotId' => $second->id,
    ]);

    $adjustment = createAdjustment($user, $company, $warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'counted_quantity' => 14,
            'lots' => [
                ['lot_id' => $first->id, 'counted_quantity' => 10],
                ['lot_id' => $second->id, 'counted_quantity' => 4],
            ],
        ]],
    ]);

    $line = $adjustment->lines->first();
    $saved = $line->lots()->orderBy('line_number')->get();
    $dropped = $saved->last();

    /** El segundo lote deja de venir: solo se contó el primero. */
    $payload = adjustmentPayload($warehouse, $item, $unit, [
        'lines' => [[
            'id' => $line->id,
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'counted_quantity' => 10,
            'lots' => [[
                'id' => $saved->first()->id,
                'lot_id' => $first->id,
                'counted_quantity' => 10,
            ]],
        ]],
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('adjustments.update', ['company' => $company->id, 'id' => $adjustment->id]),
            $payload,
        )
        ->assertSessionHasNoErrors();

    expect(AdjustmentLineLot::where('adjustment_line_id', $line->id)->count())->toBe(2);
    expect(AdjustmentLineLot::find($dropped->id)->status)->toBe('inactive');

    /** La línea vuelve a ser la suma de los lotes vivos: solo el primero. */
    $line->refresh();
    expect((float) $line->system_quantity)->toBe(10.0);
    expect((float) $line->difference_quantity)->toBe(0.0);
});

test('an adjustment sent to approval can no longer be edited', function () {
    [$user, $company, $warehouse, $location, $item, $unit] = adjustmentScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, [
        'quantity' => 10,
        'unitCost' => 25,
    ]);

    $adjustment = createAdjustment($user, $company, $warehouse, $item, $unit);

    moveAdjustmentTo($user, $company, $adjustment, 'pending_approval')
        ->assertSessionHasNoErrors();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('adjustments.update', ['company' => $company->id, 'id' => $adjustment->id]),
            adjustmentPayload($warehouse, $item, $unit, ['reason' => 'Otro motivo.']),
        )
        ->assertSessionHasErrors('status');

    expect(Adjustment::find($adjustment->id)->reason)->toBe('Conteo físico de fin de mes.');
});

test('the edit screen renders the adjustment with its catalogs', function () {
    [$user, $company, $warehouse, , $item, $unit] = adjustmentScenario();

    $adjustment = createAdjustment($user, $company, $warehouse, $item, $unit);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('adjustments.edit', ['company' => $company->id, 'id' => $adjustment->id]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('adjustments/edit')
            ->where('adjustment.code', 'AJU000001')
            ->has('adjustment.lines', 1)
            ->has('options.warehouses')
        );
});
