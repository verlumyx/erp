<?php

declare(strict_types=1);

use App\Modules\Dispatch\Models\Dispatch;

use function Pest\Laravel\actingAs;

test('it returns load plan details and its dispatches', function () {
    [$user, $company, $warehouse, $route, $vehicle] = loadPlanScenario();

    $dispatch = Dispatch::factory()->create([
        'company_id' => $company->id,
        'warehouse_id' => $warehouse->id,
        'route_id' => $route->id,
        'total_weight' => 120.0,
        'total_volume' => 1.5,
        'status' => 'confirmed',
    ]);

    $plan = createLoadPlan($user, $company, $route, $warehouse, $vehicle, [
        'driver_name' => 'Conductor de Prueba',
        'dispatches' => [
            ['dispatch_id' => $dispatch->id, 'sequence' => 1],
        ],
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('load-plans.show', ['company' => $company->id, 'id' => $plan->id]));

    $response->assertOk();
    $data = $response->viewData('page')['props']['loadPlan'];

    expect($data['id'])->toBe($plan->id);
    expect($data['code'])->toBe($plan->code);
    expect($data['driver_name'])->toBe('Conductor de Prueba');
    expect($data['vehicle_plate'])->toBe($vehicle->plate);
    expect($data['total_dispatches'])->toBe(1);
    expect(count($data['dispatches']))->toBe(1);
    expect($data['dispatches'][0]['dispatch_id'])->toBe($dispatch->id);
});

test('cannot view a load plan from another company', function () {
    [$userA, $companyA, $warehouseA, $routeA, $vehicleA] = loadPlanScenario();
    [$userB, $companyB] = createUserWithCompany();

    $plan = createLoadPlan($userA, $companyA, $routeA, $warehouseA, $vehicleA);

    actingAs($userB)
        ->withSession(['current_company_id' => $companyB->id])
        ->get(route('load-plans.show', ['company' => $companyB->id, 'id' => $plan->id]))
        ->assertNotFound();
});
