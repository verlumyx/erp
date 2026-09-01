<?php

declare(strict_types=1);

use App\Modules\Client\Models\Client;
use App\Modules\Dispatch\Models\Dispatch;
use App\Modules\Route\Models\Route;
use App\Modules\Route\Models\RouteStop;

use function Pest\Laravel\actingAs;

test('the stops of a day are generated from the fixed clients', function () {
    [$user, $company, $client] = routeScenario();

    $other = Client::factory()->create(['company_id' => $company->id]);

    $route = createRoute($user, $company, [
        'clients' => [['client_id' => $other->id], ['client_id' => $client->id]],
    ]);

    planRoute($user, $company, $route)->assertSessionHasNoErrors();

    $stops = routeStopsOn($route);

    expect($stops)->toHaveCount(2);
    /** El orden de la plantilla es el orden del recorrido. */
    expect($stops[0]->client_id)->toBe($other->id);
    expect($stops[0]->sequence)->toBe(1);
    expect($stops[1]->client_id)->toBe($client->id);
    expect($stops[0]->stop_status)->toBe('pending');
    expect($stops[0]->company_id)->toBe($company->id);
});

test('planning the same day twice does not duplicate the stops', function () {
    [$user, $company, $client] = routeScenario();

    $route = createRoute($user, $company, [
        'clients' => [['client_id' => $client->id]],
    ]);

    planRoute($user, $company, $route)->assertSessionHasNoErrors();
    planRoute($user, $company, $route)->assertSessionHasNoErrors();

    expect(RouteStop::count())->toBe(1);
});

test('replanning does not rewrite a stop the driver already closed', function () {
    [$user, $company, $client] = routeScenario();

    $route = createRoute($user, $company, [
        'clients' => [['client_id' => $client->id]],
    ]);

    planRoute($user, $company, $route)->assertSessionHasNoErrors();

    $stop = routeStopsOn($route)->first();

    registerRouteStopVisit($user, $company, $route, $stop, [
        'stop_status' => 'skipped',
        'skip_reason' => 'El local estaba cerrado',
    ])->assertSessionHasNoErrors();

    planRoute($user, $company, $route)->assertSessionHasNoErrors();

    $stop->refresh();

    /** Lo que pasó, pasó: replanificar no borra la bitácora del día. */
    expect($stop->stop_status)->toBe('skipped');
    expect($stop->skip_reason)->toBe('El local estaba cerrado');
    expect($stop->status)->toBe('active');
});

test('a client removed from the template drops out of the next plan', function () {
    [$user, $company, $client] = routeScenario();

    $route = createRoute($user, $company, [
        'clients' => [['client_id' => $client->id]],
    ]);

    planRoute($user, $company, $route)->assertSessionHasNoErrors();

    $stop = routeStopsOn($route)->first();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('routes.update', ['company' => $company->id, 'id' => $route->id]), [
            'name' => $route->name,
            'type' => $route->type,
            'frequency' => $route->frequency,
            'clients' => [],
        ])->assertSessionHasNoErrors();

    planRoute($user, $company, $route)->assertSessionHasNoErrors();

    /** No se borra, se retira del día: política de no borrado. */
    expect(RouteStop::find($stop->id)->status)->toBe('inactive');
    expect(routeStopsOn($route))->toHaveCount(0);
});

test('a pending dispatch adds its client to the day and gets tied to the stop', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = dispatchScenario();

    $route = createRoute($user, $company);

    $dispatch = createDispatch($user, $company, $client, $warehouse, $item, $unit, [
        'route_id' => $route->id,
    ]);

    planRoute($user, $company, $route)->assertSessionHasNoErrors();

    $stops = routeStopsOn($route);

    /** La plantilla estaba vacía: el cliente entra por su despacho. */
    expect($stops)->toHaveCount(1);
    expect($stops[0]->client_id)->toBe($client->id);

    /** `route_stop_id` existe justo para esto, y este es el momento de saberlo. */
    expect(Dispatch::find($dispatch->id)->route_stop_id)->toBe($stops[0]->id);
});

test('a client with both a template row and a dispatch is visited once', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = dispatchScenario();

    $route = createRoute($user, $company, [
        'clients' => [['client_id' => $client->id]],
    ]);

    createDispatch($user, $company, $client, $warehouse, $item, $unit, [
        'route_id' => $route->id,
    ]);

    planRoute($user, $company, $route)->assertSessionHasNoErrors();

    expect(routeStopsOn($route))->toHaveCount(1);
});

test('a delivered dispatch no longer adds a stop', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = dispatchScenario();

    $route = createRoute($user, $company);

    $dispatch = createDispatch($user, $company, $client, $warehouse, $item, $unit, [
        'route_id' => $route->id,
    ]);

    /** Su viaje terminó: no hay nada que planificar por él. */
    Dispatch::whereKey($dispatch->id)->update(['delivery_status' => 'delivered']);

    planRoute($user, $company, $route)->assertSessionHasNoErrors();

    expect(routeStopsOn($route))->toHaveCount(0);
});

test('the load of the day has to fit in the vehicle', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = dispatchScenario();

    $route = createRoute($user, $company, ['vehicle_capacity_weight' => 1]);

    $dispatch = createDispatch($user, $company, $client, $warehouse, $item, $unit, [
        'route_id' => $route->id,
    ]);

    Dispatch::whereKey($dispatch->id)->update(['total_weight' => 500]);

    planRoute($user, $company, $route)->assertSessionHasErrors('stop_date');

    expect(RouteStop::count())->toBe(0);
});

test('with no capacity declared nothing is checked', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = dispatchScenario();

    $route = createRoute($user, $company);

    $dispatch = createDispatch($user, $company, $client, $warehouse, $item, $unit, [
        'route_id' => $route->id,
    ]);

    Dispatch::whereKey($dispatch->id)->update(['total_weight' => 99999]);

    planRoute($user, $company, $route)->assertSessionHasNoErrors();

    expect(routeStopsOn($route))->toHaveCount(1);
});

test('a deactivated route is not planned', function () {
    [$user, $company, $client] = routeScenario();

    $route = createRoute($user, $company, [
        'clients' => [['client_id' => $client->id]],
    ]);

    Route::whereKey($route->id)->update(['status' => 'inactive']);

    planRoute($user, $company, $route->refresh())->assertSessionHasErrors('stop_date');

    expect(RouteStop::count())->toBe(0);
});

test('planning needs its own permission', function () {
    [$user, $company] = routeScenario();

    $route = createRoute($user, $company);

    assignRoleWithPermissions($user, $company, ['routes.list', 'routes.show']);

    planRoute($user, $company, $route)->assertForbidden();
});
