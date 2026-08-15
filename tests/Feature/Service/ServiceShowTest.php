<?php

declare(strict_types=1);

use App\Modules\Service\Models\Service;

use function Pest\Laravel\actingAs;

test('the service show page renders', function () {
    [$user, $company] = createUserWithCompany();

    $service = Service::factory()->create(['company_id' => $company->id, 'name' => 'Netflix']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('services.show', ['company' => $company->id, 'id' => $service->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('services/show')
        ->where('service.id', $service->id)
        ->where('service.name', 'Netflix')
    );
});

test('a service from another company cannot be viewed', function () {
    [$user, $company] = createUserWithCompany();
    [, $otherCompany] = createUserWithCompany();

    $service = Service::factory()->create(['company_id' => $otherCompany->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('services.show', ['company' => $company->id, 'id' => $service->id]));

    $response->assertNotFound();
});

test('the edit page renders', function () {
    [$user, $company] = createUserWithCompany();

    $service = Service::factory()->create(['company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('services.edit', ['company' => $company->id, 'id' => $service->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('services/edit')
        ->where('service.id', $service->id)
    );
});
