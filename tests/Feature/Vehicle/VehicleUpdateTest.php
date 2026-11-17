<?php

declare(strict_types=1);

use function Pest\Laravel\actingAs;

test('a vehicle can be updated', function () {
    [$user, $company] = vehicleScenario();

    $vehicle = createVehicle($user, $company, [
        'plate' => 'OLD123',
        'name' => 'Nombre Viejo',
        'capacity_weight' => 3000.0,
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('vehicles.update', ['company' => $company->id, 'id' => $vehicle->id]), vehiclePayload([
            'plate' => 'NEW123',
            'name' => 'Nombre Nuevo',
            'capacity_weight' => 4500.0,
        ]))
        ->assertSessionHasNoErrors();

    $vehicle->refresh();
    expect($vehicle->plate)->toBe('NEW123');
    expect($vehicle->name)->toBe('Nombre Nuevo');
    expect((float) $vehicle->capacity_weight)->toBe(4500.0);
});

test('updating with a duplicate plate of another vehicle in same company fails', function () {
    [$user, $company] = vehicleScenario();

    $first = createVehicle($user, $company, ['plate' => 'PLATE1']);
    $second = createVehicle($user, $company, ['plate' => 'PLATE2']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('vehicles.update', ['company' => $company->id, 'id' => $second->id]), vehiclePayload([
            'plate' => 'PLATE1',
        ]))
        ->assertSessionHasErrors(['plate']);
});
