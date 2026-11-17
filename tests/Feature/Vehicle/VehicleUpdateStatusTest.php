<?php

declare(strict_types=1);

use function Pest\Laravel\actingAs;

test('a vehicle can change status between active, maintenance and inactive', function () {
    [$user, $company] = vehicleScenario();

    $vehicle = createVehicle($user, $company);
    expect($vehicle->status)->toBe('active');

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('vehicles.update-status', ['company' => $company->id, 'id' => $vehicle->id]), [
            'status' => 'maintenance',
        ])
        ->assertSessionHasNoErrors();

    expect($vehicle->refresh()->status)->toBe('maintenance');

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('vehicles.update-status', ['company' => $company->id, 'id' => $vehicle->id]), [
            'status' => 'inactive',
        ])
        ->assertSessionHasNoErrors();

    expect($vehicle->refresh()->status)->toBe('inactive');
});

test('an invalid status is rejected', function () {
    [$user, $company] = vehicleScenario();

    $vehicle = createVehicle($user, $company);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('vehicles.update-status', ['company' => $company->id, 'id' => $vehicle->id]), [
            'status' => 'destruido',
        ])
        ->assertSessionHasErrors(['status']);
});
