<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

test('the list shows the adjustments of the company', function () {
    [$user, $company, $warehouse, $location, $item, $unit] = adjustmentScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, [
        'quantity' => 10,
        'unitCost' => 25,
    ]);

    createAdjustment($user, $company, $warehouse, $item, $unit);
    createAdjustment($user, $company, $warehouse, $item, $unit, [
        'type' => 'loss',
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'counted_quantity' => 9,
        ]],
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('adjustments.index', ['company' => $company->id]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('adjustments/index')
            ->has('adjustments', 2)
            ->where('meta.total', 2)
            ->has('warehouses')
        );
});

test('the list filters by type', function () {
    [$user, $company, $warehouse, $location, $item, $unit] = adjustmentScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, [
        'quantity' => 10,
        'unitCost' => 25,
    ]);

    createAdjustment($user, $company, $warehouse, $item, $unit);
    createAdjustment($user, $company, $warehouse, $item, $unit, [
        'type' => 'loss',
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'counted_quantity' => 9,
        ]],
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('adjustments.index', ['company' => $company->id, 'type' => 'loss']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('adjustments', 1)
            ->where('adjustments.0.type', 'loss')
        );
});

test('the show screen renders the adjustment with its lines', function () {
    [$user, $company, $warehouse, , $item, $unit] = adjustmentScenario();

    $adjustment = createAdjustment($user, $company, $warehouse, $item, $unit);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('adjustments.show', ['company' => $company->id, 'id' => $adjustment->id]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('adjustments/show')
            ->where('adjustment.code', 'AJU000001')
            ->has('adjustment.lines', 1)
            ->where('canApprove', true)
        );
});

test('the stock endpoint answers what the system says about an existence', function () {
    [$user, $company, $warehouse, $location, $item, $unit] = adjustmentScenario();

    registerInventoryMovement($company, $item, $warehouse, $location, [
        'quantity' => 10,
        'unitCost' => 25,
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->getJson(route('adjustments.stock', [
            'company' => $company->id,
            'warehouse_id' => $warehouse->id,
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
        ]))
        ->assertOk()
        ->assertJson([
            'system_quantity' => '10',
            'average_cost' => '25',
        ]);
});

test('a user without the list permission cannot reach the adjustments', function () {
    [$user, $company] = adjustmentScenario();

    restrictPermissions($user, $company, ['items.list']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('adjustments.index', ['company' => $company->id]))
        ->assertForbidden();
});
