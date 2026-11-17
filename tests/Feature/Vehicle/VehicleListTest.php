<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

test('the list shows the vehicles of the active company', function () {
    [$user, $company] = vehicleScenario();

    createVehicle($user, $company, ['plate' => 'ABC111', 'name' => 'Camión Uno']);
    createVehicle($user, $company, ['plate' => 'ABC222', 'name' => 'Camión Dos']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('vehicles.index', ['company' => $company->id]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('vehicles/index')
            ->has('vehicles', 2)
            ->where('meta.total', 2));
});

test('the list filters by plate, name and search query', function () {
    [$user, $company] = vehicleScenario();

    createVehicle($user, $company, ['plate' => 'CARGO1', 'name' => 'Ford Cargo']);
    createVehicle($user, $company, ['plate' => 'NPR002', 'name' => 'Chevrolet NPR']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('vehicles.index', ['company' => $company->id, 'q' => 'Cargo']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('vehicles', 1)
            ->where('vehicles.0.plate', 'CARGO1'));
});

test('the list filters by vehicle_type and status', function () {
    [$user, $company] = vehicleScenario();

    createVehicle($user, $company, [
        'plate' => 'PICK01',
        'vehicle_type' => 'pickup',
    ]);
    $maintenance = createVehicle($user, $company, [
        'plate' => 'TRUCK1',
        'vehicle_type' => 'truck',
    ]);
    $maintenance->update(['status' => 'maintenance']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('vehicles.index', ['company' => $company->id, 'status' => 'maintenance']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('vehicles', 1)
            ->where('vehicles.0.plate', 'TRUCK1'));
});

test('listing vehicles needs its own permission', function () {
    [$user, $company] = vehicleScenario();

    assignRoleWithPermissions($user, $company, []);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('vehicles.index', ['company' => $company->id]))
        ->assertForbidden();
});
