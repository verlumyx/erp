<?php

declare(strict_types=1);

use App\Modules\MeasurementUnit\Models\MeasurementUnit;

use function Pest\Laravel\actingAs;

test('a measurement unit can be updated', function () {
    [$user, $company] = createUserWithCompany();

    $unit = MeasurementUnit::factory()->create([
        'company_id' => $company->id,
        'name' => 'Kilo',
        'abbreviation' => 'k',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('measurement-units.update', ['company' => $company->id, 'id' => $unit->id]), [
            'name' => 'Kilogramo',
            'abbreviation' => 'kg',
            'description' => 'Unidad de masa',
        ]);

    $response->assertRedirect(route('measurement-units.show', ['company' => $company->id, 'id' => $unit->id]));
    $response->assertSessionHasNoErrors();

    $unit->refresh();
    expect($unit->name)->toBe('Kilogramo');
    expect($unit->abbreviation)->toBe('kg');
    expect($unit->description)->toBe('Unidad de masa');
});

test('updating keeps its own name and abbreviation valid', function () {
    [$user, $company] = createUserWithCompany();

    $unit = MeasurementUnit::factory()->create([
        'company_id' => $company->id,
        'name' => 'Litro',
        'abbreviation' => 'lt',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('measurement-units.update', ['company' => $company->id, 'id' => $unit->id]), [
            'name' => 'Litro',
            'abbreviation' => 'lt',
            'description' => 'Actualizada',
        ]);

    $response->assertSessionHasNoErrors();
    expect($unit->fresh()->description)->toBe('Actualizada');
});

test('the updated name must not collide with another unit', function () {
    [$user, $company] = createUserWithCompany();

    MeasurementUnit::factory()->create(['company_id' => $company->id, 'name' => 'Kilogramo']);
    $unit = MeasurementUnit::factory()->create(['company_id' => $company->id, 'name' => 'Litro']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('measurement-units.update', ['company' => $company->id, 'id' => $unit->id]), [
            'name' => 'Kilogramo',
            'abbreviation' => $unit->abbreviation,
        ]);

    $response->assertSessionHasErrors('name');
    expect($unit->fresh()->name)->toBe('Litro');
});

test('the updated abbreviation must not collide with another unit', function () {
    [$user, $company] = createUserWithCompany();

    MeasurementUnit::factory()->create(['company_id' => $company->id, 'abbreviation' => 'kg']);
    $unit = MeasurementUnit::factory()->create(['company_id' => $company->id, 'abbreviation' => 'lt']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('measurement-units.update', ['company' => $company->id, 'id' => $unit->id]), [
            'name' => $unit->name,
            'abbreviation' => 'kg',
        ]);

    $response->assertSessionHasErrors('abbreviation');
    expect($unit->fresh()->abbreviation)->toBe('lt');
});

test('a user without permission cannot update a measurement unit', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['measurement-units.list']);

    $unit = MeasurementUnit::factory()->create(['company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('measurement-units.update', ['company' => $company->id, 'id' => $unit->id]), [
            'name' => 'Forbidden',
            'abbreviation' => 'fb',
        ]);

    $response->assertForbidden();
});
