<?php

declare(strict_types=1);

use App\Modules\Plan\Models\Plan;
use App\Modules\Service\Models\Service;

use function Pest\Laravel\actingAs;

test('the plans index renders with plans', function () {
    [$user, $company] = createUserWithCompany();
    $service = Service::factory()->create(['company_id' => $company->id]);

    Plan::factory()->count(3)->create([
        'company_id' => $company->id,
        'service_id' => $service->id,
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('plans.index', ['company' => $company->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('plans/index')
        ->has('plans', 3)
        ->where('meta.total', 3)
    );
});

test('the index exposes the active services for the form', function () {
    [$user, $company] = createUserWithCompany();
    Service::factory()->create(['company_id' => $company->id]);
    Service::factory()->inactive()->create(['company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('plans.index', ['company' => $company->id]));

    $response->assertInertia(fn ($page) => $page->has('services', 1));
});

test('plans can be filtered by name', function () {
    [$user, $company] = createUserWithCompany();
    $service = Service::factory()->create(['company_id' => $company->id]);

    Plan::factory()->create(['company_id' => $company->id, 'service_id' => $service->id, 'name' => 'Netflix Mensual']);
    Plan::factory()->create(['company_id' => $company->id, 'service_id' => $service->id, 'name' => 'Disney Anual']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('plans.index', ['company' => $company->id, 'name' => 'Netflix']));

    $response->assertInertia(fn ($page) => $page
        ->has('plans', 1)
        ->where('plans.0.name', 'Netflix Mensual')
    );
});

test('plans can be filtered by capacity', function () {
    [$user, $company] = createUserWithCompany();
    $service = Service::factory()->create(['company_id' => $company->id]);

    $target = Plan::factory()->create(['company_id' => $company->id, 'service_id' => $service->id, 'capacity' => 'full_account']);
    Plan::factory()->create(['company_id' => $company->id, 'service_id' => $service->id, 'capacity' => 'profile']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('plans.index', ['company' => $company->id, 'capacity' => 'full_account']));

    $response->assertInertia(fn ($page) => $page
        ->has('plans', 1)
        ->where('plans.0.id', $target->id)
    );
});

test('plans can be filtered by active state', function () {
    [$user, $company] = createUserWithCompany();
    $service = Service::factory()->create(['company_id' => $company->id]);

    $active = Plan::factory()->create(['company_id' => $company->id, 'service_id' => $service->id]);
    Plan::factory()->inactive()->create(['company_id' => $company->id, 'service_id' => $service->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('plans.index', ['company' => $company->id, 'active' => '1']));

    $response->assertInertia(fn ($page) => $page
        ->has('plans', 1)
        ->where('plans.0.id', $active->id)
    );
});

test('plans are scoped to the current company', function () {
    [$user, $company] = createUserWithCompany();
    [, $otherCompany] = createUserWithCompany();
    $service = Service::factory()->create(['company_id' => $company->id]);
    $otherService = Service::factory()->create(['company_id' => $otherCompany->id]);

    Plan::factory()->create(['company_id' => $company->id, 'service_id' => $service->id]);
    Plan::factory()->create(['company_id' => $otherCompany->id, 'service_id' => $otherService->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('plans.index', ['company' => $company->id]));

    $response->assertInertia(fn ($page) => $page->has('plans', 1));
});

test('a user without permission cannot list plans', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['services.list']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('plans.index', ['company' => $company->id]));

    $response->assertForbidden();
});
