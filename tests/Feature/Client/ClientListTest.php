<?php

declare(strict_types=1);

use App\Modules\Client\Models\Client;

use function Pest\Laravel\actingAs;

test('the clients index renders with clients', function () {
    [$user, $company] = createUserWithCompany();

    Client::factory()->count(3)->create(['company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('clients.index', ['company' => $company->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('clients/index')
        ->has('clients', 3)
        ->where('meta.total', 3)
    );
});

test('the clients index includes each client active platforms', function () {
    $ctx = makeSaleContext();
    persistSale($ctx); // venta activa sobre el cliente con su servicio

    $response = actingAs($ctx['user'])
        ->withSession(['current_company_id' => $ctx['company']->id])
        ->get(route('clients.index', ['company' => $ctx['company']->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('clients/index')
        ->where('clients.0.id', $ctx['client']->id)
        ->has('clients.0.platforms', 1)
        ->where('clients.0.platforms.0.id', $ctx['service']->id)
        ->where('clients.0.platforms.0.name', $ctx['service']->name)
    );
});

test('the clients index does not list platforms for non-active sales', function () {
    $ctx = makeSaleContext();
    persistSale($ctx, status: 'expired');

    $response = actingAs($ctx['user'])
        ->withSession(['current_company_id' => $ctx['company']->id])
        ->get(route('clients.index', ['company' => $ctx['company']->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('clients/index')
        ->where('clients.0.id', $ctx['client']->id)
        ->has('clients.0.platforms', 0)
    );
});

test('clients can be filtered by name', function () {
    [$user, $company] = createUserWithCompany();

    Client::factory()->create(['company_id' => $company->id, 'name' => 'Findable Client', 'email' => 'findable@acme.test']);
    Client::factory()->create(['company_id' => $company->id, 'name' => 'Other Client', 'email' => 'other@acme.test']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('clients.index', ['company' => $company->id, 'name' => 'Findable']));

    $response->assertInertia(fn ($page) => $page
        ->has('clients', 1)
        ->where('clients.0.name', 'Findable Client')
    );
});

test('clients can be filtered by email', function () {
    [$user, $company] = createUserWithCompany();

    $target = Client::factory()->create(['company_id' => $company->id, 'name' => 'Alpha', 'email' => 'target@acme.test']);
    Client::factory()->create(['company_id' => $company->id, 'name' => 'Beta', 'email' => 'other@acme.test']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('clients.index', ['company' => $company->id, 'email' => 'target@']));

    $response->assertInertia(fn ($page) => $page
        ->has('clients', 1)
        ->where('clients.0.id', $target->id)
    );
});

test('clients can be filtered by phone', function () {
    [$user, $company] = createUserWithCompany();

    $target = Client::factory()->create(['company_id' => $company->id, 'phone' => '+58 412 111 2233']);
    Client::factory()->create(['company_id' => $company->id, 'phone' => '+58 414 999 8877']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('clients.index', ['company' => $company->id, 'phone' => '412 111']));

    $response->assertInertia(fn ($page) => $page
        ->has('clients', 1)
        ->where('clients.0.id', $target->id)
    );
});

test('client field filters combine with AND', function () {
    [$user, $company] = createUserWithCompany();

    $target = Client::factory()->create(['company_id' => $company->id, 'name' => 'Maria', 'email' => 'maria@acme.test']);
    Client::factory()->create(['company_id' => $company->id, 'name' => 'Maria', 'email' => 'other@acme.test']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('clients.index', ['company' => $company->id, 'name' => 'Maria', 'email' => 'maria@']));

    $response->assertInertia(fn ($page) => $page
        ->has('clients', 1)
        ->where('clients.0.id', $target->id)
    );
});

test('clients can be filtered by status', function () {
    [$user, $company] = createUserWithCompany();

    Client::factory()->create(['company_id' => $company->id, 'status' => 'active']);
    Client::factory()->inactive()->create(['company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('clients.index', ['company' => $company->id, 'status' => 'inactive']));

    $response->assertInertia(fn ($page) => $page->has('clients', 1));
});

test('clients can be filtered by code', function () {
    [$user, $company] = createUserWithCompany();

    $target = Client::factory()->create(['company_id' => $company->id, 'code' => 'CLI000042']);
    Client::factory()->create(['company_id' => $company->id, 'code' => 'CLI000099']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('clients.index', ['company' => $company->id, 'code' => 'CLI000042']));

    $response->assertInertia(fn ($page) => $page
        ->has('clients', 1)
        ->where('clients.0.id', $target->id)
    );
});

test('the index only shows clients from the active company', function () {
    [$user, $company] = createUserWithCompany();

    Client::factory()->count(2)->create(['company_id' => $company->id]);
    Client::factory()->count(3)->create();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('clients.index', ['company' => $company->id]));

    $response->assertInertia(fn ($page) => $page
        ->has('clients', 2)
        ->where('meta.total', 2)
    );
});

test('a user without permission cannot list clients', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, []);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('clients.index', ['company' => $company->id]));

    $response->assertForbidden();
});
