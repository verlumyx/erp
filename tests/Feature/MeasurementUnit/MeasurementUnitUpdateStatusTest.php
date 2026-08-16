<?php

declare(strict_types=1);

use App\Modules\MeasurementUnit\Models\MeasurementUnit;

use function Pest\Laravel\actingAs;

test('a measurement unit status can be changed', function () {
    [$user, $company] = createUserWithCompany();

    $unit = MeasurementUnit::factory()->create(['company_id' => $company->id, 'status' => 'active']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('measurement-units.update-status', ['company' => $company->id, 'id' => $unit->id]), [
            'status' => 'inactive',
        ]);

    $response->assertRedirect(route('measurement-units.index', ['company' => $company->id]));
    $response->assertSessionHas('success');
    expect($unit->fresh()->status)->toBe('inactive');
});

test('the status must be a valid value', function () {
    [$user, $company] = createUserWithCompany();

    $unit = MeasurementUnit::factory()->create(['company_id' => $company->id, 'status' => 'active']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('measurement-units.update-status', ['company' => $company->id, 'id' => $unit->id]), [
            'status' => 'deleted',
        ]);

    $response->assertSessionHasErrors('status');
    expect($unit->fresh()->status)->toBe('active');
});

test('a user without permission cannot change the measurement unit status', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['measurement-units.list']);

    $unit = MeasurementUnit::factory()->create(['company_id' => $company->id, 'status' => 'active']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('measurement-units.update-status', ['company' => $company->id, 'id' => $unit->id]), [
            'status' => 'inactive',
        ]);

    $response->assertForbidden();
});
