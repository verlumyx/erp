<?php

declare(strict_types=1);

use App\Modules\MeasurementUnit\Models\MeasurementUnit;

use function Pest\Laravel\actingAs;

test('the measurement units index renders with units', function () {
    [$user, $company] = createUserWithCompany();

    MeasurementUnit::factory()->count(3)->create(['company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('measurement-units.index', ['company' => $company->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('measurement-units/index')
        ->has('measurementUnits', 3)
        ->where('meta.total', 3)
    );
});

test('measurement units can be filtered by name', function () {
    [$user, $company] = createUserWithCompany();

    MeasurementUnit::factory()->create(['company_id' => $company->id, 'name' => 'Kilogramo']);
    MeasurementUnit::factory()->create(['company_id' => $company->id, 'name' => 'Litro']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('measurement-units.index', ['company' => $company->id, 'name' => 'Kilo']));

    $response->assertInertia(fn ($page) => $page
        ->has('measurementUnits', 1)
        ->where('measurementUnits.0.name', 'Kilogramo')
    );
});

test('measurement units can be filtered by abbreviation', function () {
    [$user, $company] = createUserWithCompany();

    $target = MeasurementUnit::factory()->create(['company_id' => $company->id, 'abbreviation' => 'kg']);
    MeasurementUnit::factory()->create(['company_id' => $company->id, 'abbreviation' => 'lt']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('measurement-units.index', ['company' => $company->id, 'abbreviation' => 'kg']));

    $response->assertInertia(fn ($page) => $page
        ->has('measurementUnits', 1)
        ->where('measurementUnits.0.id', $target->id)
    );
});

test('measurement units can be filtered by code', function () {
    [$user, $company] = createUserWithCompany();

    $target = MeasurementUnit::factory()->create(['company_id' => $company->id, 'code' => 'UOM000042']);
    MeasurementUnit::factory()->create(['company_id' => $company->id, 'code' => 'UOM000099']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('measurement-units.index', ['company' => $company->id, 'code' => 'UOM000042']));

    $response->assertInertia(fn ($page) => $page
        ->has('measurementUnits', 1)
        ->where('measurementUnits.0.id', $target->id)
    );
});

test('measurement units can be filtered by status', function () {
    [$user, $company] = createUserWithCompany();

    MeasurementUnit::factory()->create(['company_id' => $company->id, 'status' => 'active']);
    MeasurementUnit::factory()->inactive()->create(['company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('measurement-units.index', ['company' => $company->id, 'status' => 'inactive']));

    $response->assertInertia(fn ($page) => $page->has('measurementUnits', 1));
});

test('measurement unit filters combine with AND', function () {
    [$user, $company] = createUserWithCompany();

    $target = MeasurementUnit::factory()->create([
        'company_id' => $company->id,
        'name' => 'Caja grande',
        'abbreviation' => 'cjg',
    ]);
    MeasurementUnit::factory()->create([
        'company_id' => $company->id,
        'name' => 'Caja pequeña',
        'abbreviation' => 'cjp',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('measurement-units.index', [
            'company' => $company->id,
            'name' => 'Caja',
            'abbreviation' => 'cjg',
        ]));

    $response->assertInertia(fn ($page) => $page
        ->has('measurementUnits', 1)
        ->where('measurementUnits.0.id', $target->id)
    );
});

test('the index only shows measurement units from the active company', function () {
    [$user, $company] = createUserWithCompany();

    MeasurementUnit::factory()->count(2)->create(['company_id' => $company->id]);
    MeasurementUnit::factory()->count(3)->create();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('measurement-units.index', ['company' => $company->id]));

    $response->assertInertia(fn ($page) => $page
        ->has('measurementUnits', 2)
        ->where('meta.total', 2)
    );
});

test('a user without permission cannot list measurement units', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, []);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('measurement-units.index', ['company' => $company->id]));

    $response->assertForbidden();
});
