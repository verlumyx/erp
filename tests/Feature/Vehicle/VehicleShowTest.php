<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

test('the detail shows the vehicle information and capacities', function () {
    [$user, $company] = vehicleScenario();

    $vehicle = createVehicle($user, $company, [
        'plate' => 'XYZ999',
        'name' => 'Gandola Pesada',
        'capacity_weight' => 28000.0,
        'capacity_volume' => 85.0,
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('vehicles.show', ['company' => $company->id, 'id' => $vehicle->id]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('vehicles/show')
            ->where('vehicle.id', $vehicle->id)
            ->where('vehicle.plate', 'XYZ999')
            ->where('vehicle.name', 'Gandola Pesada')
            ->where('vehicle.capacity_weight', 28000)
            ->where('vehicle.capacity_volume', 85));
});

test('a vehicle of another company is not reachable', function () {
    [$firstUser, $firstCompany] = vehicleScenario();
    [$secondUser, $secondCompany] = vehicleScenario();

    $vehicle = createVehicle($firstUser, $firstCompany);

    actingAs($secondUser)
        ->withSession(['current_company_id' => $secondCompany->id])
        ->get(route('vehicles.show', ['company' => $secondCompany->id, 'id' => $vehicle->id]))
        ->assertNotFound();
});
