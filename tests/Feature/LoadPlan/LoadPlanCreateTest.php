<?php

declare(strict_types=1);

use App\Modules\Dispatch\Models\Dispatch;

use function Pest\Laravel\actingAs;

test('a load plan is created draft with its sequential code', function () {
    [$user, $company, $warehouse, $route, $vehicle] = loadPlanScenario();

    $loadPlan = createLoadPlan($user, $company, $route, $warehouse, $vehicle, [
        'driver_name' => 'Mario Rossi',
        'helper_name' => 'Luigi Rossi',
        'description' => 'Ruta matutina de prueba',
    ]);

    expect($loadPlan->code)->toBe('PLC000001');
    expect($loadPlan->status)->toBe('draft');
    expect($loadPlan->route_id)->toBe($route->id);
    expect($loadPlan->warehouse_id)->toBe($warehouse->id);
    expect($loadPlan->vehicle_id)->toBe($vehicle->id);
    expect($loadPlan->driver_name)->toBe('Mario Rossi');
    expect($loadPlan->helper_name)->toBe('Luigi Rossi');
    expect($loadPlan->description)->toBe('Ruta matutina de prueba');
    expect($loadPlan->total_dispatches)->toBe(0);
});

test('a load plan can be created with dispatches and totals are aggregated', function () {
    [$user, $company, $warehouse, $route, $vehicle] = loadPlanScenario();

    $dispatchA = Dispatch::factory()->create([
        'company_id' => $company->id,
        'warehouse_id' => $warehouse->id,
        'route_id' => $route->id,
        'total_weight' => 150.5,
        'total_volume' => 1.25,
        'status' => 'confirmed',
    ]);

    $dispatchB = Dispatch::factory()->create([
        'company_id' => $company->id,
        'warehouse_id' => $warehouse->id,
        'route_id' => $route->id,
        'total_weight' => 249.5,
        'total_volume' => 2.75,
        'status' => 'confirmed',
    ]);

    $loadPlan = createLoadPlan($user, $company, $route, $warehouse, $vehicle, [
        'dispatches' => [
            ['dispatch_id' => $dispatchA->id, 'sequence' => 1],
            ['dispatch_id' => $dispatchB->id, 'sequence' => 2],
        ],
    ]);

    expect($loadPlan->total_dispatches)->toBe(2);
    expect((float) $loadPlan->total_weight)->toBe(400.0);
    expect((float) $loadPlan->total_volume)->toBe(4.0);

    expect($loadPlan->planDispatches()->count())->toBe(2);
    expect($loadPlan->planDispatches()->where('dispatch_id', $dispatchA->id)->first()->sequence)->toBe(1);
    expect($loadPlan->planDispatches()->where('dispatch_id', $dispatchB->id)->first()->sequence)->toBe(2);
});

test('cannot assign a dispatch already assigned to another active load plan', function () {
    [$user, $company, $warehouse, $route, $vehicle] = loadPlanScenario();

    $dispatch = Dispatch::factory()->create([
        'company_id' => $company->id,
        'warehouse_id' => $warehouse->id,
        'route_id' => $route->id,
        'status' => 'confirmed',
    ]);

    // Create first load plan containing the dispatch
    createLoadPlan($user, $company, $route, $warehouse, $vehicle, [
        'dispatches' => [
            ['dispatch_id' => $dispatch->id, 'sequence' => 1],
        ],
    ]);

    // Attempt to assign the same dispatch in a new load plan
    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('load-plans.store', ['company' => $company->id]), loadPlanPayload($route, $warehouse, $vehicle, [
            'dispatches' => [
                ['dispatch_id' => $dispatch->id, 'sequence' => 1],
            ],
        ]))
        ->assertSessionHasErrors(['dispatches.0.dispatch_id']);
});

test('route, warehouse, vehicle and driver_name are required', function () {
    [$user, $company, $warehouse, $route, $vehicle] = loadPlanScenario();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('load-plans.store', ['company' => $company->id]), [
            'plan_date' => now()->toDateString(),
        ])
        ->assertSessionHasErrors(['route_id', 'warehouse_id', 'vehicle_id', 'driver_name']);
});
