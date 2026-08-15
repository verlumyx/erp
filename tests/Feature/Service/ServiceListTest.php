<?php

declare(strict_types=1);

use App\Modules\Service\Models\Service;

use function Pest\Laravel\actingAs;

test('the services index renders with services', function () {
    [$user, $company] = createUserWithCompany();

    Service::factory()->count(3)->create(['company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('services.index', ['company' => $company->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('services/index')
        ->has('services', 3)
        ->where('meta.total', 3)
    );
});

test('services can be filtered by name', function () {
    [$user, $company] = createUserWithCompany();

    Service::factory()->create(['company_id' => $company->id, 'name' => 'Netflix']);
    Service::factory()->create(['company_id' => $company->id, 'name' => 'Disney Plus']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('services.index', ['company' => $company->id, 'name' => 'Netflix']));

    $response->assertInertia(fn ($page) => $page
        ->has('services', 1)
        ->where('services.0.name', 'Netflix')
    );
});

test('services can be filtered by code', function () {
    [$user, $company] = createUserWithCompany();

    $target = Service::factory()->create(['company_id' => $company->id, 'code' => 'SER000777']);
    Service::factory()->create(['company_id' => $company->id, 'code' => 'SER000888']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('services.index', ['company' => $company->id, 'code' => '000777']));

    $response->assertInertia(fn ($page) => $page
        ->has('services', 1)
        ->where('services.0.id', $target->id)
    );
});

test('services can be filtered by active state', function () {
    [$user, $company] = createUserWithCompany();

    $active = Service::factory()->create(['company_id' => $company->id]);
    Service::factory()->inactive()->create(['company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('services.index', ['company' => $company->id, 'active' => '1']));

    $response->assertInertia(fn ($page) => $page
        ->has('services', 1)
        ->where('services.0.id', $active->id)
    );
});

test('services are scoped to the current company', function () {
    [$user, $company] = createUserWithCompany();
    [, $otherCompany] = createUserWithCompany();

    Service::factory()->create(['company_id' => $company->id]);
    Service::factory()->create(['company_id' => $otherCompany->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('services.index', ['company' => $company->id]));

    $response->assertInertia(fn ($page) => $page->has('services', 1));
});

test('a user without permission cannot list services', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['clients.list']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('services.index', ['company' => $company->id]));

    $response->assertForbidden();
});
