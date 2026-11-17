<?php

declare(strict_types=1);

use App\Modules\Dispatch\Models\Dispatch;

use function Pest\Laravel\actingAs;

test('a draft load plan can be updated and dispatches recalculated', function () {
    [$user, $company, $warehouse, $route, $vehicle] = loadPlanScenario();

    $dispatchA = Dispatch::factory()->create([
        'company_id' => $company->id,
        'warehouse_id' => $warehouse->id,
        'route_id' => $route->id,
        'total_weight' => 100.0,
        'total_volume' => 1.0,
        'status' => 'confirmed',
    ]);

    $dispatchB = Dispatch::factory()->create([
        'company_id' => $company->id,
        'warehouse_id' => $warehouse->id,
        'route_id' => $route->id,
        'total_weight' => 200.0,
        'total_volume' => 2.0,
        'status' => 'confirmed',
    ]);

    $plan = createLoadPlan($user, $company, $route, $warehouse, $vehicle, [
        'driver_name' => 'Chofer Inicial',
        'dispatches' => [
            ['dispatch_id' => $dispatchA->id, 'sequence' => 1],
        ],
    ]);

    expect($plan->total_dispatches)->toBe(1);
    expect((float) $plan->total_weight)->toBe(100.0);

    // Update with dispatch B instead of dispatch A
    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('load-plans.update', ['company' => $company->id, 'id' => $plan->id]), loadPlanPayload($route, $warehouse, $vehicle, [
            'driver_name' => 'Chofer Modificado',
            'dispatches' => [
                ['dispatch_id' => $dispatchB->id, 'sequence' => 1],
            ],
        ]))
        ->assertSessionHasNoErrors();

    $plan->refresh();
    expect($plan->driver_name)->toBe('Chofer Modificado');
    expect($plan->total_dispatches)->toBe(1);
    expect((float) $plan->total_weight)->toBe(200.0);
    expect((float) $plan->total_volume)->toBe(2.0);

    // Dispatch A should now be inactive on this plan
    expect($plan->planDispatches()->where('dispatch_id', $dispatchA->id)->where('status', 'active')->exists())->toBeFalse();
    expect($plan->planDispatches()->where('dispatch_id', $dispatchB->id)->where('status', 'active')->exists())->toBeTrue();
});

test('cannot update a load plan from another company', function () {
    [$userA, $companyA, $warehouseA, $routeA, $vehicleA] = loadPlanScenario();
    [$userB, $companyB, $warehouseB, $routeB, $vehicleB] = loadPlanScenario();

    $plan = createLoadPlan($userA, $companyA, $routeA, $warehouseA, $vehicleA);

    actingAs($userB)
        ->withSession(['current_company_id' => $companyB->id])
        ->put(route('load-plans.update', ['company' => $companyB->id, 'id' => $plan->id]), loadPlanPayload($routeB, $warehouseB, $vehicleB, [
            'driver_name' => 'Intento Hack',
        ]))
        ->assertNotFound();
});
