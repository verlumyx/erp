<?php

declare(strict_types=1);

use App\Modules\MeasurementUnit\Models\MeasurementUnit;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

test('a measurement unit can be created', function () {
    [$user, $company] = createUserWithCompany();

    $id = (string) Str::uuid7();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('measurement-units.store', ['company' => $company->id]), [
            'id' => $id,
            'name' => 'Kilogramo',
            'abbreviation' => 'kg',
            'description' => 'Unidad de masa',
        ]);

    $response->assertRedirect(route('measurement-units.index', ['company' => $company->id]));
    $response->assertSessionHasNoErrors();

    $unit = MeasurementUnit::find($id);
    expect($unit)->not->toBeNull();
    expect($unit->name)->toBe('Kilogramo');
    expect($unit->abbreviation)->toBe('kg');
    expect($unit->status)->toBe('active');
    expect($unit->created_by)->toBe($user->id);
    expect($unit->company_id)->toBe($company->id);
    expect($unit->code)->toBe('UOM000001');
});

test('the code auto-increments per company', function () {
    [$user, $company] = createUserWithCompany();

    $first = (string) Str::uuid7();
    $second = (string) Str::uuid7();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('measurement-units.store', ['company' => $company->id]), [
            'id' => $first,
            'name' => 'Unidad',
            'abbreviation' => 'un',
        ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('measurement-units.store', ['company' => $company->id]), [
            'id' => $second,
            'name' => 'Caja',
            'abbreviation' => 'cja',
        ]);

    expect(MeasurementUnit::find($first)->code)->toBe('UOM000001');
    expect(MeasurementUnit::find($second)->code)->toBe('UOM000002');
});

test('each company has its own code sequence', function () {
    [$userA, $companyA] = createUserWithCompany();
    [$userB, $companyB] = createUserWithCompany();

    $idA = (string) Str::uuid7();
    $idB = (string) Str::uuid7();

    actingAs($userA)
        ->withSession(['current_company_id' => $companyA->id])
        ->post(route('measurement-units.store', ['company' => $companyA->id]), [
            'id' => $idA,
            'name' => 'Litro',
            'abbreviation' => 'l',
        ]);

    actingAs($userB)
        ->withSession(['current_company_id' => $companyB->id])
        ->post(route('measurement-units.store', ['company' => $companyB->id]), [
            'id' => $idB,
            'name' => 'Litro',
            'abbreviation' => 'l',
        ]);

    expect(MeasurementUnit::find($idA)->code)->toBe('UOM000001');
    expect(MeasurementUnit::find($idB)->code)->toBe('UOM000001');
});

test('a measurement unit can be created without a description', function () {
    [$user, $company] = createUserWithCompany();

    $id = (string) Str::uuid7();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('measurement-units.store', ['company' => $company->id]), [
            'id' => $id,
            'name' => 'Unidad',
            'abbreviation' => 'un',
        ]);

    $response->assertSessionHasNoErrors();
    expect(MeasurementUnit::find($id)?->description)->toBeNull();
});

test('the name is required', function () {
    [$user, $company] = createUserWithCompany();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('measurement-units.store', ['company' => $company->id]), [
            'id' => (string) Str::uuid7(),
            'name' => '',
            'abbreviation' => 'un',
        ]);

    $response->assertSessionHasErrors('name');
});

test('the abbreviation is required', function () {
    [$user, $company] = createUserWithCompany();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('measurement-units.store', ['company' => $company->id]), [
            'id' => (string) Str::uuid7(),
            'name' => 'Unidad',
            'abbreviation' => '',
        ]);

    $response->assertSessionHasErrors('abbreviation');
});

test('the name must be unique per company', function () {
    [$user, $company] = createUserWithCompany();

    MeasurementUnit::factory()->create(['company_id' => $company->id, 'name' => 'Kilogramo']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('measurement-units.store', ['company' => $company->id]), [
            'id' => (string) Str::uuid7(),
            'name' => 'Kilogramo',
            'abbreviation' => 'kgs',
        ]);

    $response->assertSessionHasErrors('name');
});

test('the abbreviation must be unique per company', function () {
    [$user, $company] = createUserWithCompany();

    MeasurementUnit::factory()->create(['company_id' => $company->id, 'abbreviation' => 'kg']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('measurement-units.store', ['company' => $company->id]), [
            'id' => (string) Str::uuid7(),
            'name' => 'Kilo gramos',
            'abbreviation' => 'kg',
        ]);

    $response->assertSessionHasErrors('abbreviation');
});

test('another company can reuse the same name and abbreviation', function () {
    [$user, $company] = createUserWithCompany();

    MeasurementUnit::factory()->create(['name' => 'Kilogramo', 'abbreviation' => 'kg']);

    $id = (string) Str::uuid7();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('measurement-units.store', ['company' => $company->id]), [
            'id' => $id,
            'name' => 'Kilogramo',
            'abbreviation' => 'kg',
        ]);

    $response->assertSessionHasNoErrors();
    expect(MeasurementUnit::find($id))->not->toBeNull();
});

test('a user without permission cannot create a measurement unit', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['measurement-units.list']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('measurement-units.store', ['company' => $company->id]), [
            'id' => (string) Str::uuid7(),
            'name' => 'Forbidden',
            'abbreviation' => 'fb',
        ]);

    $response->assertForbidden();
});
