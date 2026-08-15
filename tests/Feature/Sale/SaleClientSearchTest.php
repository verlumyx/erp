<?php

declare(strict_types=1);

use App\Modules\Client\Models\Client;

use function Pest\Laravel\actingAs;

test('the client search returns matching active clients limited to 20', function () {
    [$user, $company] = createUserWithCompany();

    Client::factory()->create([
        'company_id' => $company->id,
        'name' => 'Carlos Pérez',
        'status' => 'active',
    ]);
    Client::factory()->create([
        'company_id' => $company->id,
        'name' => 'Ana Gómez',
        'status' => 'active',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->getJson(route('sales.clients.search', ['company' => $company->id, 'q' => 'carlos']));

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
    $response->assertJsonPath('data.0.name', 'Carlos Pérez');
});

test('the client search excludes inactive clients', function () {
    [$user, $company] = createUserWithCompany();

    Client::factory()->inactive()->create([
        'company_id' => $company->id,
        'name' => 'Cliente Inactivo',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->getJson(route('sales.clients.search', ['company' => $company->id, 'q' => 'inactivo']));

    $response->assertOk();
    $response->assertJsonCount(0, 'data');
});

test('an empty term returns the initial batch of active clients', function () {
    [$user, $company] = createUserWithCompany();

    Client::factory()->count(3)->create([
        'company_id' => $company->id,
        'status' => 'active',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->getJson(route('sales.clients.search', ['company' => $company->id]));

    $response->assertOk();
    $response->assertJsonCount(3, 'data');
});

test('a user without create permission cannot search clients', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['sales.list']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->getJson(route('sales.clients.search', ['company' => $company->id, 'q' => 'x']));

    $response->assertForbidden();
});
