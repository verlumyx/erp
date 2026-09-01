<?php

declare(strict_types=1);

use App\Modules\Route\Models\RouteStop;

use function Pest\Laravel\actingAs;

test('a visit is registered on the stop', function () {
    [$user, $company, $client] = routeScenario();

    $route = createRoute($user, $company, ['clients' => [['client_id' => $client->id]]]);
    planRoute($user, $company, $route)->assertSessionHasNoErrors();

    $stop = routeStopsOn($route)->first();

    registerRouteStopVisit($user, $company, $route, $stop, [
        'stop_status' => 'completed',
        'actual_arrival' => now()->setTime(9, 15)->format('Y-m-d H:i:s'),
        'actual_departure' => now()->setTime(9, 40)->format('Y-m-d H:i:s'),
        'latitude' => 10.4806,
        'longitude' => -66.9036,
    ])->assertSessionHasNoErrors();

    $stop->refresh();

    expect($stop->stop_status)->toBe('completed');
    expect($stop->actual_arrival->format('H:i'))->toBe('09:15');
    expect($stop->actual_departure->format('H:i'))->toBe('09:40');
    expect((float) $stop->latitude)->toBe(10.4806);
    expect($stop->skip_reason)->toBeNull();
});

test('a visit that did not happen has to be explained', function () {
    [$user, $company, $client] = routeScenario();

    $route = createRoute($user, $company, ['clients' => [['client_id' => $client->id]]]);
    planRoute($user, $company, $route)->assertSessionHasNoErrors();

    $stop = routeStopsOn($route)->first();

    registerRouteStopVisit($user, $company, $route, $stop, ['stop_status' => 'skipped'])
        ->assertSessionHasErrors('skip_reason');

    expect($stop->refresh()->stop_status)->toBe('pending');
});

test('the reason is dropped when the visit did happen', function () {
    [$user, $company, $client] = routeScenario();

    $route = createRoute($user, $company, ['clients' => [['client_id' => $client->id]]]);
    planRoute($user, $company, $route)->assertSessionHasNoErrors();

    $stop = routeStopsOn($route)->first();

    registerRouteStopVisit($user, $company, $route, $stop, [
        'stop_status' => 'completed',
        'skip_reason' => 'Sobra: sí se visitó',
    ])->assertSessionHasNoErrors();

    expect($stop->refresh()->skip_reason)->toBeNull();
});

test('arriving is not closing: the stop can still be registered again', function () {
    [$user, $company, $client] = routeScenario();

    $route = createRoute($user, $company, ['clients' => [['client_id' => $client->id]]]);
    planRoute($user, $company, $route)->assertSessionHasNoErrors();

    $stop = routeStopsOn($route)->first();

    registerRouteStopVisit($user, $company, $route, $stop, ['stop_status' => 'arrived'])
        ->assertSessionHasNoErrors();

    expect($stop->refresh()->stop_status)->toBe('arrived');

    registerRouteStopVisit($user, $company, $route, $stop, ['stop_status' => 'completed'])
        ->assertSessionHasNoErrors();

    expect($stop->refresh()->stop_status)->toBe('completed');
});

test('a closed stop is not rewritten', function () {
    [$user, $company, $client] = routeScenario();

    $route = createRoute($user, $company, ['clients' => [['client_id' => $client->id]]]);
    planRoute($user, $company, $route)->assertSessionHasNoErrors();

    $stop = routeStopsOn($route)->first();

    registerRouteStopVisit($user, $company, $route, $stop, ['stop_status' => 'completed'])
        ->assertSessionHasNoErrors();

    registerRouteStopVisit($user, $company, $route, $stop, [
        'stop_status' => 'failed',
        'skip_reason' => 'Rectificando la historia',
    ])->assertSessionHasErrors('stop_status');

    expect($stop->refresh()->stop_status)->toBe('completed');
});

test('the stop cannot be left before arriving at it', function () {
    [$user, $company, $client] = routeScenario();

    $route = createRoute($user, $company, ['clients' => [['client_id' => $client->id]]]);
    planRoute($user, $company, $route)->assertSessionHasNoErrors();

    $stop = routeStopsOn($route)->first();

    registerRouteStopVisit($user, $company, $route, $stop, [
        'actual_arrival' => now()->setTime(10, 0)->format('Y-m-d H:i:s'),
        'actual_departure' => now()->setTime(9, 0)->format('Y-m-d H:i:s'),
    ])->assertSessionHasErrors('actual_departure');
});

test('a stop of another route is not reachable', function () {
    [$user, $company, $client] = routeScenario();

    $route = createRoute($user, $company);
    $other = createRoute($user, $company);

    $stop = RouteStop::factory()->create([
        'company_id' => $company->id,
        'route_id' => $other->id,
        'client_id' => $client->id,
    ]);

    registerRouteStopVisit($user, $company, $route, $stop)->assertNotFound();
});

test('registering a visit needs its own permission', function () {
    [$user, $company, $client] = routeScenario();

    $route = createRoute($user, $company, ['clients' => [['client_id' => $client->id]]]);
    planRoute($user, $company, $route)->assertSessionHasNoErrors();

    $stop = routeStopsOn($route)->first();

    assignRoleWithPermissions($user, $company, ['routes.list', 'routes.show']);

    registerRouteStopVisit($user, $company, $route, $stop)->assertForbidden();
});
