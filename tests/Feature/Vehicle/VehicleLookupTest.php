<?php

declare(strict_types=1);

use function Pest\Laravel\actingAs;

test('the lookup returns the vehicles as remote select options', function () {
    [$user, $company] = vehicleScenario();

    createVehicle($user, $company, [
        'plate' => 'ABC789',
        'name' => 'Cava 350',
        'capacity_weight' => 3500.0,
        'capacity_volume' => 14.0,
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('vehicles.lookup', ['company' => $company->id, 'q' => 'ABC789']))
        ->assertOk()
        ->json();

    expect($response['data'])->toHaveCount(1);
    expect($response['data'][0]['meta']['plate'])->toBe('ABC789');
    expect((float) $response['data'][0]['meta']['capacity_weight'])->toEqual(3500.0);
    expect((float) $response['data'][0]['meta']['capacity_volume'])->toEqual(14.0);
});

test('the lookup filters by ids', function () {
    [$user, $company] = vehicleScenario();

    $vehicle1 = createVehicle($user, $company);
    $vehicle2 = createVehicle($user, $company);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('vehicles.lookup', ['company' => $company->id, 'ids' => $vehicle1->id]))
        ->assertOk()
        ->json();

    expect($response['data'])->toHaveCount(1);
    expect($response['data'][0]['value'])->toBe($vehicle1->id);
});

test('the lookup only sees the vehicles of the active company', function () {
    [$firstUser, $firstCompany] = vehicleScenario();
    [$secondUser, $secondCompany] = vehicleScenario();

    createVehicle($firstUser, $firstCompany, ['plate' => 'PLATE1']);
    createVehicle($secondUser, $secondCompany, ['plate' => 'PLATE2']);

    $response = actingAs($firstUser)
        ->withSession(['current_company_id' => $firstCompany->id])
        ->get(route('vehicles.lookup', ['company' => $firstCompany->id]))
        ->assertOk()
        ->json();

    expect($response['data'])->toHaveCount(1);
    expect($response['data'][0]['meta']['plate'])->toBe('PLATE1');
});
