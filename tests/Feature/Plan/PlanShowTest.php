<?php

declare(strict_types=1);

use App\Modules\Plan\Models\Plan;
use App\Modules\Service\Models\Service;

use function Pest\Laravel\actingAs;

test('the plan show page renders with the plan and its service', function () {
    [$user, $company] = createUserWithCompany();
    $service = Service::factory()->create(['company_id' => $company->id, 'name' => 'Netflix']);
    $plan = Plan::factory()->create(['company_id' => $company->id, 'service_id' => $service->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('plans.show', ['company' => $company->id, 'id' => $plan->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('plans/show')
        ->where('plan.id', $plan->id)
        ->where('plan.service.name', 'Netflix')
    );
});

test('a plan from another company cannot be shown', function () {
    [$user, $company] = createUserWithCompany();
    [, $otherCompany] = createUserWithCompany();
    $service = Service::factory()->create(['company_id' => $otherCompany->id]);
    $plan = Plan::factory()->create(['company_id' => $otherCompany->id, 'service_id' => $service->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('plans.show', ['company' => $company->id, 'id' => $plan->id]));

    $response->assertNotFound();
});

test('a user without permission cannot view a plan', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['plans.list']);
    $service = Service::factory()->create(['company_id' => $company->id]);
    $plan = Plan::factory()->create(['company_id' => $company->id, 'service_id' => $service->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('plans.show', ['company' => $company->id, 'id' => $plan->id]));

    $response->assertForbidden();
});
