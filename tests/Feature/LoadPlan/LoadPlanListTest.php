<?php

declare(strict_types=1);

use function Pest\Laravel\actingAs;

test('it lists load plans of the company', function () {
    [$user, $company, $warehouse, $route, $vehicle] = loadPlanScenario();
    [$otherUser, $otherCompany, $otherWarehouse, $otherRoute, $otherVehicle] = loadPlanScenario();

    createLoadPlan($user, $company, $route, $warehouse, $vehicle, ['driver_name' => 'Chofer 1']);
    createLoadPlan($user, $company, $route, $warehouse, $vehicle, ['driver_name' => 'Chofer 2']);
    createLoadPlan($otherUser, $otherCompany, $otherRoute, $otherWarehouse, $otherVehicle, ['driver_name' => 'Otro Chofer']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('load-plans.index', ['company' => $company->id]));

    $response->assertOk();
    $data = $response->viewData('page')['props']['loadPlans'];

    expect(count($data))->toBe(2);
    expect($data[0]['company_id'])->toBe($company->id);
    expect($data[1]['company_id'])->toBe($company->id);
});

test('it filters by route_id', function () {
    [$user, $company, $warehouse, $routeA, $vehicle] = loadPlanScenario();
    $routeB = createRoute($user, $company, ['name' => 'Ruta B']);

    createLoadPlan($user, $company, $routeA, $warehouse, $vehicle);
    createLoadPlan($user, $company, $routeB, $warehouse, $vehicle);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('load-plans.index', [
            'company' => $company->id,
            'route_id' => $routeA->id,
        ]));

    $response->assertOk();
    $data = $response->viewData('page')['props']['loadPlans'];

    expect(count($data))->toBe(1);
    expect($data[0]['route_id'])->toBe($routeA->id);
});

test('it filters by vehicle_id', function () {
    [$user, $company, $warehouse, $route, $vehicleA] = loadPlanScenario();
    $vehicleB = createVehicle($user, $company, ['plate' => 'B99ZZ1A']);

    createLoadPlan($user, $company, $route, $warehouse, $vehicleA);
    createLoadPlan($user, $company, $route, $warehouse, $vehicleB);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('load-plans.index', [
            'company' => $company->id,
            'vehicle_id' => $vehicleB->id,
        ]));

    $response->assertOk();
    $data = $response->viewData('page')['props']['loadPlans'];

    expect(count($data))->toBe(1);
    expect($data[0]['vehicle_id'])->toBe($vehicleB->id);
});

test('it filters by status', function () {
    [$user, $company, $warehouse, $route, $vehicle] = loadPlanScenario();

    $plan = createLoadPlan($user, $company, $route, $warehouse, $vehicle);
    $plan->update(['status' => 'in_transit']);

    createLoadPlan($user, $company, $route, $warehouse, $vehicle); // draft

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('load-plans.index', [
            'company' => $company->id,
            'status' => 'in_transit',
        ]));

    $response->assertOk();
    $data = $response->viewData('page')['props']['loadPlans'];

    expect(count($data))->toBe(1);
    expect($data[0]['status'])->toBe('in_transit');
});

test('it filters by text query q', function () {
    [$user, $company, $warehouse, $route, $vehicle] = loadPlanScenario();

    createLoadPlan($user, $company, $route, $warehouse, $vehicle, ['driver_name' => 'Roberto Gómez Bolaños']);
    createLoadPlan($user, $company, $route, $warehouse, $vehicle, ['driver_name' => 'Armando Mendoza']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('load-plans.index', [
            'company' => $company->id,
            'q' => 'Bolaños',
        ]));

    $response->assertOk();
    $data = $response->viewData('page')['props']['loadPlans'];

    expect(count($data))->toBe(1);
    expect($data[0]['driver_name'])->toBe('Roberto Gómez Bolaños');
});
