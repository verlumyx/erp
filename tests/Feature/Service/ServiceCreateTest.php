<?php

declare(strict_types=1);

use App\Modules\Service\Models\Service;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

test('a service can be created', function () {
    [$user, $company] = createUserWithCompany();

    $id = (string) Str::uuid7();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('services.store', ['company' => $company->id]), [
            'id' => $id,
            'name' => 'Netflix',
            'logo_url' => 'https://logo.test/netflix.png',
            'max_profiles' => 5,
        ]);

    $response->assertRedirect(route('services.index', ['company' => $company->id]));
    $response->assertSessionHasNoErrors();

    $service = Service::find($id);
    expect($service)->not->toBeNull();
    expect($service->name)->toBe('Netflix');
    expect($service->max_profiles)->toBe(5);
    expect($service->active)->toBeTrue();
    expect($service->company_id)->toBe($company->id);
    expect($service->code)->toBe('SER000001');
});

test('the code auto-increments per company', function () {
    [$user, $company] = createUserWithCompany();

    $first = (string) Str::uuid7();
    $second = (string) Str::uuid7();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('services.store', ['company' => $company->id]), [
            'id' => $first,
            'name' => 'Netflix',
            'max_profiles' => 4,
        ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('services.store', ['company' => $company->id]), [
            'id' => $second,
            'name' => 'Disney+',
            'max_profiles' => 4,
        ]);

    expect(Service::find($first)->code)->toBe('SER000001');
    expect(Service::find($second)->code)->toBe('SER000002');
});

test('each company has its own code sequence', function () {
    [$userA, $companyA] = createUserWithCompany();
    [$userB, $companyB] = createUserWithCompany();

    $idA = (string) Str::uuid7();
    $idB = (string) Str::uuid7();

    actingAs($userA)
        ->withSession(['current_company_id' => $companyA->id])
        ->post(route('services.store', ['company' => $companyA->id]), [
            'id' => $idA,
            'name' => 'Netflix',
            'max_profiles' => 4,
        ]);

    actingAs($userB)
        ->withSession(['current_company_id' => $companyB->id])
        ->post(route('services.store', ['company' => $companyB->id]), [
            'id' => $idB,
            'name' => 'Netflix',
            'max_profiles' => 4,
        ]);

    expect(Service::find($idA)->code)->toBe('SER000001');
    expect(Service::find($idB)->code)->toBe('SER000001');
});

test('a service can be created without optional fields', function () {
    [$user, $company] = createUserWithCompany();

    $id = (string) Str::uuid7();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('services.store', ['company' => $company->id]), [
            'id' => $id,
            'name' => 'Spotify',
            'max_profiles' => 1,
        ]);

    $response->assertSessionHasNoErrors();
    expect(Service::find($id)?->logo_url)->toBeNull();
});

test('the name is required', function () {
    [$user, $company] = createUserWithCompany();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('services.store', ['company' => $company->id]), [
            'id' => (string) Str::uuid7(),
            'name' => '',
            'max_profiles' => 1,
        ]);

    $response->assertSessionHasErrors('name');
});

test('max_profiles must be at least 1', function () {
    [$user, $company] = createUserWithCompany();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('services.store', ['company' => $company->id]), [
            'id' => (string) Str::uuid7(),
            'name' => 'Bad',
            'max_profiles' => 0,
        ]);

    $response->assertSessionHasErrors('max_profiles');
});

test('the name must be unique per company', function () {
    [$user, $company] = createUserWithCompany();

    Service::factory()->create(['company_id' => $company->id, 'name' => 'Netflix']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('services.store', ['company' => $company->id]), [
            'id' => (string) Str::uuid7(),
            'name' => 'Netflix',
            'max_profiles' => 4,
        ]);

    $response->assertSessionHasErrors('name');
});

test('the same name can exist in different companies', function () {
    [$userA, $companyA] = createUserWithCompany();
    [$userB, $companyB] = createUserWithCompany();

    Service::factory()->create(['company_id' => $companyA->id, 'name' => 'Netflix']);

    $response = actingAs($userB)
        ->withSession(['current_company_id' => $companyB->id])
        ->post(route('services.store', ['company' => $companyB->id]), [
            'id' => (string) Str::uuid7(),
            'name' => 'Netflix',
            'max_profiles' => 4,
        ]);

    $response->assertSessionHasNoErrors();
});

test('a user without permission cannot create a service', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['services.list']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('services.store', ['company' => $company->id]), [
            'id' => (string) Str::uuid7(),
            'name' => 'Forbidden',
            'max_profiles' => 1,
        ]);

    $response->assertForbidden();
});
