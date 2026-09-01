<?php

declare(strict_types=1);

use App\Modules\Route\Models\Route;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

test('the list shows the routes of the active company', function () {
    [$user, $company] = routeScenario();

    createRoute($user, $company, ['name' => 'Zona Norte']);
    createRoute($user, $company, ['name' => 'Zona Sur']);

    /** Una ruta de otra empresa no se cuela en el listado. */
    [$stranger, $otherCompany] = createUserWithCompany();
    Route::factory()->create([
        'company_id' => $otherCompany->id,
        'created_by' => $stranger->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('routes.index', ['company' => $company->id]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('routes/index')
            ->has('routes', 2)
            ->has('options.warehouses')
            ->has('options.users')
            ->where('meta.total', 2));
});

test('the list counts the fixed clients of each route', function () {
    [$user, $company, $client] = routeScenario();

    createRoute($user, $company, ['clients' => [['client_id' => $client->id]]]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('routes.index', ['company' => $company->id]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('routes.0.clients_count', 1));
});

test('the list filters by name', function () {
    [$user, $company] = routeScenario();

    createRoute($user, $company, ['name' => 'Zona Norte']);
    createRoute($user, $company, ['name' => 'Zona Sur']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('routes.index', ['company' => $company->id, 'name' => 'Norte']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('routes', 1)
            ->where('routes.0.name', 'Zona Norte'));
});

test('the list filters by type and by zone', function () {
    [$user, $company] = routeScenario();

    createRoute($user, $company, ['type' => 'collection', 'zone' => 'Centro']);
    createRoute($user, $company, ['type' => 'delivery', 'zone' => 'Litoral']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('routes.index', ['company' => $company->id, 'type' => 'collection']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('routes', 1)
            ->where('routes.0.zone', 'Centro'));

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('routes.index', ['company' => $company->id, 'zone' => 'Litoral']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->has('routes', 1));
});

test('the list answers by which route a client is reached', function () {
    [$user, $company, $client] = routeScenario();

    createRoute($user, $company, [
        'name' => 'La suya',
        'clients' => [['client_id' => $client->id]],
    ]);
    createRoute($user, $company, ['name' => 'Otra']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('routes.index', ['company' => $company->id, 'client_id' => $client->id]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('routes', 1)
            ->where('routes.0.name', 'La suya'));
});

test('listing routes needs its own permission', function () {
    [$user, $company] = routeScenario();

    assignRoleWithPermissions($user, $company, ['routes.create']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('routes.index', ['company' => $company->id]))
        ->assertForbidden();
});
