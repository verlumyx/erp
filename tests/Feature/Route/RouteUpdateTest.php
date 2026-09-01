<?php

declare(strict_types=1);

use App\Modules\Client\Models\Client;
use App\Modules\Route\Models\RouteClient;

use function Pest\Laravel\actingAs;

/**
 * Sends a `routes.update` payload built from the route as it stands.
 *
 * @param  array<string, mixed>  $overrides
 */
function updateRoute(
    App\Modules\User\Models\User $user,
    App\Modules\Company\Models\Company $company,
    App\Modules\Route\Models\Route $route,
    array $overrides = [],
): Illuminate\Testing\TestResponse {
    return actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('routes.update', ['company' => $company->id, 'id' => $route->id]),
            [
                'name' => $route->name,
                'type' => $route->type,
                'frequency' => $route->frequency,
                'weekdays' => $route->weekdays,
                'clients' => [],
                ...$overrides,
            ],
        );
}

test('the header of a route is edited at any time', function () {
    [$user, $company, , $warehouse] = routeScenario();

    $route = createRoute($user, $company);

    updateRoute($user, $company, $route, [
        'name' => 'Zona Sur - Martes',
        'type' => 'mixed',
        'frequency' => 'daily',
        'weekdays' => [],
        'warehouse_id' => $warehouse->id,
        'vehicle_plate' => 'AB123CD',
        'vehicle_capacity_weight' => 1500,
        'estimated_distance_km' => 42.5,
    ])->assertSessionHasNoErrors();

    $route->refresh();

    expect($route->name)->toBe('Zona Sur - Martes');
    expect($route->type)->toBe('mixed');
    expect($route->frequency)->toBe('daily');
    expect($route->warehouse_id)->toBe($warehouse->id);
    expect((float) $route->vehicle_capacity_weight)->toBe(1500.0);
    expect((float) $route->estimated_distance_km)->toBe(42.5);
    /** El código no se reescribe al editar. */
    expect($route->code)->toBe('RUT000001');
});

test('a client removed from the route is deactivated, never deleted', function () {
    [$user, $company, $client] = routeScenario();

    $route = createRoute($user, $company, [
        'clients' => [['client_id' => $client->id]],
    ]);

    $row = $route->clients->first();

    updateRoute($user, $company, $route, ['clients' => []])
        ->assertSessionHasNoErrors();

    /** Política de no borrado: la fila sigue ahí, apagada. */
    expect(RouteClient::count())->toBe(1);
    expect(RouteClient::find($row->id)->status)->toBe('inactive');
});

test('adding the same client again reuses its row instead of duplicating it', function () {
    [$user, $company, $client] = routeScenario();

    $route = createRoute($user, $company, [
        'clients' => [['client_id' => $client->id]],
    ]);

    $row = $route->clients->first();

    updateRoute($user, $company, $route, ['clients' => []])
        ->assertSessionHasNoErrors();

    updateRoute($user, $company, $route, [
        'clients' => [['client_id' => $client->id]],
    ])->assertSessionHasNoErrors();

    /**
     * El índice único no deja duplicar el par (ruta, cliente, dirección): la
     * fila apagada se reutiliza.
     */
    expect(RouteClient::count())->toBe(1);
    expect(RouteClient::find($row->id)->status)->toBe('active');
});

test('reordering the clients rewrites the visiting order', function () {
    [$user, $company, $client] = routeScenario();

    $other = Client::factory()->create(['company_id' => $company->id]);

    $route = createRoute($user, $company, [
        'clients' => [['client_id' => $client->id], ['client_id' => $other->id]],
    ]);

    $rows = $route->clients->keyBy('client_id');

    updateRoute($user, $company, $route, [
        'clients' => [
            ['id' => $rows[$other->id]->id, 'client_id' => $other->id],
            ['id' => $rows[$client->id]->id, 'client_id' => $client->id],
        ],
    ])->assertSessionHasNoErrors();

    expect(RouteClient::find($rows[$other->id]->id)->sequence)->toBe(1);
    expect(RouteClient::find($rows[$client->id]->id)->sequence)->toBe(2);
});

test('a route of another company is not reachable', function () {
    [$user, $company] = routeScenario();
    [$stranger, $otherCompany] = createUserWithCompany();

    $route = createRoute($stranger, $otherCompany);

    updateRoute($user, $company, $route, ['name' => 'Robada'])
        ->assertNotFound();
});
