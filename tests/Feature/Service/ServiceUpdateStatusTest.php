<?php

declare(strict_types=1);

use App\Modules\Service\Models\Service;

use function Pest\Laravel\actingAs;

test('a service can be deactivated', function () {
    [$user, $company] = createUserWithCompany();

    $service = Service::factory()->create(['company_id' => $company->id, 'active' => true]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('services.update-status', ['company' => $company->id, 'id' => $service->id]), [
            'active' => false,
        ]);

    $response->assertRedirect(route('services.index', ['company' => $company->id]));

    expect(Service::find($service->id)->active)->toBeFalse();
});

test('a service can be reactivated', function () {
    [$user, $company] = createUserWithCompany();

    $service = Service::factory()->inactive()->create(['company_id' => $company->id]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('services.update-status', ['company' => $company->id, 'id' => $service->id]), [
            'active' => true,
        ]);

    expect(Service::find($service->id)->active)->toBeTrue();
});

test('a user without permission cannot change the status', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['services.list']);

    $service = Service::factory()->create(['company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('services.update-status', ['company' => $company->id, 'id' => $service->id]), [
            'active' => false,
        ]);

    $response->assertForbidden();
});
