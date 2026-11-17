<?php

declare(strict_types=1);

use App\Modules\Vehicle\Models\Vehicle;

use function Pest\Laravel\actingAs;

test('a vehicle is created active with its sequential code', function () {
    [$user, $company] = vehicleScenario();

    $vehicle = createVehicle($user, $company, [
        'plate' => 'A12BC3D',
        'name' => 'Ford Cargo 1721',
        'brand' => 'Ford',
        'model' => 'Cargo 1721',
        'year' => 2018,
        'vehicle_type' => 'truck',
        'capacity_weight' => 5000.0,
        'capacity_volume' => 22.0,
    ]);

    expect($vehicle->code)->toBe('CAM000001');
    expect($vehicle->status)->toBe('active');
    expect($vehicle->plate)->toBe('A12BC3D');
    expect($vehicle->name)->toBe('Ford Cargo 1721');
    expect((float) $vehicle->capacity_weight)->toBe(5000.0);
    expect((float) $vehicle->capacity_volume)->toBe(22.0);
});

test('the plate is unique per company', function () {
    [$user, $company] = vehicleScenario();

    createVehicle($user, $company, ['plate' => 'A12BC3D']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('vehicles.store', ['company' => $company->id]), vehiclePayload([
            'plate' => 'A12BC3D',
        ]))
        ->assertSessionHasErrors(['plate']);
});

test('the same plate is allowed in another company', function () {
    [$firstUser, $firstCompany] = vehicleScenario();
    [$secondUser, $secondCompany] = vehicleScenario();

    createVehicle($firstUser, $firstCompany, ['plate' => 'A12BC3D']);

    actingAs($secondUser)
        ->withSession(['current_company_id' => $secondCompany->id])
        ->post(route('vehicles.store', ['company' => $secondCompany->id]), vehiclePayload([
            'plate' => 'A12BC3D',
        ]))
        ->assertSessionHasNoErrors();

    expect(Vehicle::where('plate', 'A12BC3D')->count())->toBe(2);
});

test('plate is automatically converted to uppercase', function () {
    [$user, $company] = vehicleScenario();

    $vehicle = createVehicle($user, $company, ['plate' => 'abc123d']);

    expect($vehicle->plate)->toBe('ABC123D');
});

test('creating a vehicle needs its own permission', function () {
    [$user, $company] = vehicleScenario();

    assignRoleWithPermissions($user, $company, ['vehicles.list']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('vehicles.store', ['company' => $company->id]), vehiclePayload())
        ->assertForbidden();
});
