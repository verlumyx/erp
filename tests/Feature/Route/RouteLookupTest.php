<?php

declare(strict_types=1);

use function Pest\Laravel\actingAs;

test('the lookup returns the routes as remote select options', function () {
    [$user, $company, , $warehouse] = routeScenario();

    $route = createRoute($user, $company, [
        'name' => 'Zona Norte',
        'warehouse_id' => $warehouse->id,
        'driver_id' => $user->id,
        'vehicle_capacity_weight' => 1200,
    ]);

    $response = actingAs($user)->withSession(['current_company_id' => $company->id])
        ->getJson(route('routes.lookup', ['company' => $company->id, 'q' => 'Norte']))
        ->assertOk();

    $response->assertJsonPath('data.0.value', $route->id);
    $response->assertJsonPath('data.0.label', 'RUT000001 — Zona Norte');
    /** El `meta` lleva lo que el despacho copia al elegir la ruta. */
    $response->assertJsonPath('data.0.meta.warehouse_name', $warehouse->name);
    $response->assertJsonPath('data.0.meta.driver_name', $user->name);
    $response->assertJsonPath('data.0.meta.vehicle_capacity_weight', '1200.0000');
    $response->assertJsonPath('has_more', false);
});

test('the lookup hydrates the values already chosen by their ids', function () {
    [$user, $company] = routeScenario();

    $route = createRoute($user, $company);
    createRoute($user, $company);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->getJson(route('routes.lookup', ['company' => $company->id, 'ids' => $route->id]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.value', $route->id);
});

test('the lookup only sees the routes of the active company', function () {
    [$user, $company] = routeScenario();
    [$stranger, $otherCompany] = createUserWithCompany();

    createRoute($stranger, $otherCompany);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->getJson(route('routes.lookup', ['company' => $company->id]))
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

test('the lookup needs the list permission', function () {
    [$user, $company] = routeScenario();

    assignRoleWithPermissions($user, $company, ['routes.show']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->getJson(route('routes.lookup', ['company' => $company->id]))
        ->assertForbidden();
});
