<?php

declare(strict_types=1);

use App\Modules\Service\Models\Service;

use function Pest\Laravel\actingAs;

test('a service can be updated', function () {
    [$user, $company] = createUserWithCompany();

    $service = Service::factory()->create([
        'company_id' => $company->id,
        'name' => 'Netflix',
        'max_profiles' => 4,
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('services.update', ['company' => $company->id, 'id' => $service->id]), [
            'name' => 'Netflix Premium',
            'logo_url' => 'https://logo.test/np.png',
            'max_profiles' => 6,
        ]);

    $response->assertRedirect(route('services.show', ['company' => $company->id, 'id' => $service->id]));
    $response->assertSessionHasNoErrors();

    $service->refresh();
    expect($service->name)->toBe('Netflix Premium');
    expect($service->max_profiles)->toBe(6);
});

test('the name remains editable to its own value', function () {
    [$user, $company] = createUserWithCompany();

    $service = Service::factory()->create(['company_id' => $company->id, 'name' => 'Netflix']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('services.update', ['company' => $company->id, 'id' => $service->id]), [
            'name' => 'Netflix',
            'max_profiles' => 3,
        ]);

    $response->assertSessionHasNoErrors();
});

test('the name cannot collide with another service in the same company', function () {
    [$user, $company] = createUserWithCompany();

    Service::factory()->create(['company_id' => $company->id, 'name' => 'Disney+']);
    $service = Service::factory()->create(['company_id' => $company->id, 'name' => 'Netflix']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('services.update', ['company' => $company->id, 'id' => $service->id]), [
            'name' => 'Disney+',
            'max_profiles' => 3,
        ]);

    $response->assertSessionHasErrors('name');
});

test('a user without permission cannot update a service', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['services.list']);

    $service = Service::factory()->create(['company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('services.update', ['company' => $company->id, 'id' => $service->id]), [
            'name' => 'Hacked',
            'max_profiles' => 1,
        ]);

    $response->assertForbidden();
});
