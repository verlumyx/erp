<?php

declare(strict_types=1);

use App\Modules\Dispatch\Models\Dispatch;
use App\Modules\Route\Models\Route;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

test('a dispatch is assigned to a route when it is created', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = dispatchScenario();

    $route = createRoute($user, $company);

    $dispatch = createDispatch($user, $company, $client, $warehouse, $item, $unit, [
        'route_id' => $route->id,
    ]);

    expect($dispatch->route_id)->toBe($route->id);
    /** La parada todavía no existe: la escribe la planificación del día. */
    expect($dispatch->route_stop_id)->toBeNull();
});

test('the detail carries the route so the screen can name it', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = dispatchScenario();

    $route = createRoute($user, $company, ['name' => 'Zona Norte']);

    $dispatch = createDispatch($user, $company, $client, $warehouse, $item, $unit, [
        'route_id' => $route->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('dispatches.show', ['company' => $company->id, 'id' => $dispatch->id]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('dispatch.route_id', $route->id)
            ->where('dispatch.route_code', 'RUT000001')
            ->where('dispatch.route_name', 'Zona Norte'));
});

test('the route of a dispatch is changed while it is a draft', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = dispatchScenario();

    $first = createRoute($user, $company, ['name' => 'Zona Norte']);
    $second = createRoute($user, $company, ['name' => 'Zona Sur']);

    $dispatch = createDispatch($user, $company, $client, $warehouse, $item, $unit, [
        'route_id' => $first->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(
            route('dispatches.update', ['company' => $company->id, 'id' => $dispatch->id]),
            dispatchPayload($client, $warehouse, $item, $unit, ['route_id' => $second->id]),
        )
        ->assertSessionHasNoErrors();

    expect(Dispatch::find($dispatch->id)->route_id)->toBe($second->id);
});

test('a deactivated route is not assignable', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = dispatchScenario();

    $route = createRoute($user, $company);
    Route::whereKey($route->id)->update(['status' => 'inactive']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(
            route('dispatches.store', ['company' => $company->id]),
            dispatchPayload($client, $warehouse, $item, $unit, ['route_id' => $route->id]),
        )
        ->assertSessionHasErrors('route_id');

    expect(Dispatch::count())->toBe(0);
});

test('a route of another company is not assignable', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = dispatchScenario();
    [$stranger, $otherCompany] = createUserWithCompany();

    $route = createRoute($stranger, $otherCompany);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(
            route('dispatches.store', ['company' => $company->id]),
            dispatchPayload($client, $warehouse, $item, $unit, ['route_id' => $route->id]),
        )
        ->assertSessionHasErrors('route_id');
});

test('assigning the route is what puts the dispatch into the itinerary', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = dispatchScenario();

    $route = createRoute($user, $company);

    /** Sin ruta el despacho no entra en ningún recorrido. */
    createDispatch($user, $company, $client, $warehouse, $item, $unit);

    planRoute($user, $company, $route)->assertSessionHasNoErrors();
    expect(routeStopsOn($route))->toHaveCount(0);

    /** Asignada la ruta, la planificación le crea su parada y lo ata a ella. */
    $assigned = createDispatch($user, $company, $client, $warehouse, $item, $unit, [
        'route_id' => $route->id,
    ]);

    planRoute($user, $company, $route)->assertSessionHasNoErrors();

    $stops = routeStopsOn($route);

    expect($stops)->toHaveCount(1);
    expect(Dispatch::find($assigned->id)->route_stop_id)->toBe($stops[0]->id);
});
