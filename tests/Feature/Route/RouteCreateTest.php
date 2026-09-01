<?php

declare(strict_types=1);

use App\Modules\Client\Models\Client;
use App\Modules\Client\Models\ClientAddress;
use App\Modules\Route\Models\Route;
use App\Modules\Route\Models\RouteClient;

use function Pest\Laravel\actingAs;

test('a route is created active with its sequential code', function () {
    [$user, $company, , $warehouse] = routeScenario();

    $route = createRoute($user, $company, [
        'warehouse_id' => $warehouse->id,
        'driver_id' => $user->id,
        'zone' => 'Zona Norte',
    ]);

    expect($route->code)->toBe('RUT000001');
    /** La ruta es un maestro: nace activa, no en borrador. */
    expect($route->status)->toBe('active');
    expect($route->type)->toBe('delivery');
    expect($route->warehouse_id)->toBe($warehouse->id);
    expect($route->weekdays)->toBe(['mon', 'wed']);
    expect($route->clients)->toHaveCount(0);
});

test('the fixed clients are saved in visiting order', function () {
    [$user, $company, $client] = routeScenario();

    $other = Client::factory()->create(['company_id' => $company->id]);

    $route = createRoute($user, $company, [
        'clients' => [
            ['client_id' => $other->id],
            ['client_id' => $client->id],
        ],
    ]);

    $clients = $route->clients->sortBy('sequence')->values();

    expect($clients)->toHaveCount(2);
    /** El orden de la lista es el orden de la visita. */
    expect($clients[0]->client_id)->toBe($other->id);
    expect($clients[0]->sequence)->toBe(1);
    expect($clients[1]->client_id)->toBe($client->id);
    expect($clients[1]->sequence)->toBe(2);
    /** La empresa se hereda de la ruta, sin join contra el padre. */
    expect($clients[0]->company_id)->toBe($company->id);
});

test('the name is unique per company', function () {
    [$user, $company] = routeScenario();

    $route = createRoute($user, $company);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(
            route('routes.store', ['company' => $company->id]),
            routePayload(['name' => $route->name]),
        )
        ->assertSessionHasErrors('name');

    expect(Route::count())->toBe(1);
});

test('the same name is free in another company', function () {
    [$user, $company] = routeScenario();
    [$stranger, $otherCompany] = createUserWithCompany();

    $route = createRoute($user, $company);
    $twin = createRoute($stranger, $otherCompany, ['name' => $route->name]);

    expect($twin->name)->toBe($route->name);
    /** Cada empresa lleva su propia secuencia. */
    expect($twin->code)->toBe('RUT000001');
});

test('a client cannot be repeated in the same route with the same address', function () {
    [$user, $company, $client] = routeScenario();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(
            route('routes.store', ['company' => $company->id]),
            routePayload([
                'clients' => [
                    ['client_id' => $client->id],
                    ['client_id' => $client->id],
                ],
            ]),
        )
        ->assertSessionHasErrors('clients.1.client_id');

    expect(RouteClient::count())->toBe(0);
});

test('the address of a stop must belong to its client', function () {
    [$user, $company, $client] = routeScenario();

    $stranger = Client::factory()->create(['company_id' => $company->id]);
    $address = ClientAddress::factory()->create([
        'company_id' => $company->id,
        'client_id' => $stranger->id,
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(
            route('routes.store', ['company' => $company->id]),
            routePayload([
                'clients' => [
                    ['client_id' => $client->id, 'client_address_id' => $address->id],
                ],
            ]),
        )
        ->assertSessionHasErrors('clients.0.client_address_id');
});

test('a client of another company is rejected', function () {
    [$user, $company] = routeScenario();
    [, $otherCompany] = createUserWithCompany();

    $stranger = Client::factory()->create(['company_id' => $otherCompany->id]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(
            route('routes.store', ['company' => $company->id]),
            routePayload(['clients' => [['client_id' => $stranger->id]]]),
        )
        ->assertSessionHasErrors('clients.0.client_id');
});

test('creating a route needs its own permission', function () {
    [$user, $company] = routeScenario();

    assignRoleWithPermissions($user, $company, ['routes.list']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('routes.store', ['company' => $company->id]), routePayload())
        ->assertForbidden();

    expect(Route::count())->toBe(0);
});
