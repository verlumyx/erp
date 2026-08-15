<?php

declare(strict_types=1);

use App\Modules\Plan\Models\Plan;
use App\Modules\Service\Models\Service;

use function Pest\Laravel\actingAs;

test('a plan can be deactivated', function () {
    [$user, $company] = createUserWithCompany();
    $service = Service::factory()->create(['company_id' => $company->id]);
    $plan = Plan::factory()->create(['company_id' => $company->id, 'service_id' => $service->id, 'active' => true]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('plans.update-status', ['company' => $company->id, 'id' => $plan->id]), [
            'active' => false,
        ]);

    $response->assertRedirect(route('plans.index', ['company' => $company->id]));

    expect(Plan::find($plan->id)->active)->toBeFalse();
});

test('a plan can be reactivated', function () {
    [$user, $company] = createUserWithCompany();
    $service = Service::factory()->create(['company_id' => $company->id]);
    $plan = Plan::factory()->inactive()->create(['company_id' => $company->id, 'service_id' => $service->id]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('plans.update-status', ['company' => $company->id, 'id' => $plan->id]), [
            'active' => true,
        ]);

    expect(Plan::find($plan->id)->active)->toBeTrue();
});

test('a user without permission cannot change the plan status', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['plans.list']);
    $service = Service::factory()->create(['company_id' => $company->id]);
    $plan = Plan::factory()->create(['company_id' => $company->id, 'service_id' => $service->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('plans.update-status', ['company' => $company->id, 'id' => $plan->id]), [
            'active' => false,
        ]);

    $response->assertForbidden();
});
