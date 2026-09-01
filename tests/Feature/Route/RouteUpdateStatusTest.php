<?php

declare(strict_types=1);

use App\Modules\Dispatch\Models\Dispatch;
use App\Modules\Route\Models\Route;

use function Pest\Laravel\actingAs;

/** Moves a route to the given status over HTTP. */
function switchRouteTo(
    App\Modules\User\Models\User $user,
    App\Modules\Company\Models\Company $company,
    App\Modules\Route\Models\Route $route,
    string $status,
): Illuminate\Testing\TestResponse {
    return actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('routes.update-status', ['company' => $company->id, 'id' => $route->id]),
            ['status' => $status],
        );
}

test('a route with nothing open is deactivated', function () {
    [$user, $company] = routeScenario();

    $route = createRoute($user, $company);

    switchRouteTo($user, $company, $route, 'inactive')->assertSessionHasNoErrors();

    expect(Route::find($route->id)->status)->toBe('inactive');
});

test('a deactivated route is activated again', function () {
    [$user, $company] = routeScenario();

    $route = createRoute($user, $company);

    switchRouteTo($user, $company, $route, 'inactive')->assertSessionHasNoErrors();
    switchRouteTo($user, $company, $route->refresh(), 'active')->assertSessionHasNoErrors();

    expect(Route::find($route->id)->status)->toBe('active');
});

test('a route with stops still open is not deactivated', function () {
    [$user, $company, $client] = routeScenario();

    $route = createRoute($user, $company, ['clients' => [['client_id' => $client->id]]]);
    planRoute($user, $company, $route)->assertSessionHasNoErrors();

    switchRouteTo($user, $company, $route, 'inactive')->assertSessionHasErrors('status');

    expect(Route::find($route->id)->status)->toBe('active');
});

test('once the stops are closed the route is deactivated', function () {
    [$user, $company, $client] = routeScenario();

    $route = createRoute($user, $company, ['clients' => [['client_id' => $client->id]]]);
    planRoute($user, $company, $route)->assertSessionHasNoErrors();

    registerRouteStopVisit($user, $company, $route, routeStopsOn($route)->first())
        ->assertSessionHasNoErrors();

    switchRouteTo($user, $company, $route, 'inactive')->assertSessionHasNoErrors();

    expect(Route::find($route->id)->status)->toBe('inactive');
});

test('a route with dispatches still undelivered is not deactivated', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = dispatchScenario();

    $route = createRoute($user, $company);

    $dispatch = createDispatch($user, $company, $client, $warehouse, $item, $unit, [
        'route_id' => $route->id,
    ]);

    switchRouteTo($user, $company, $route, 'inactive')->assertSessionHasErrors('status');

    expect(Route::find($route->id)->status)->toBe('active');

    /** Entregado el despacho, la ruta ya no debe nada. */
    Dispatch::whereKey($dispatch->id)->update(['delivery_status' => 'delivered']);

    switchRouteTo($user, $company, $route, 'inactive')->assertSessionHasNoErrors();

    expect(Route::find($route->id)->status)->toBe('inactive');
});

test('an unknown status is rejected', function () {
    [$user, $company] = routeScenario();

    $route = createRoute($user, $company);

    switchRouteTo($user, $company, $route, 'cancelled')->assertSessionHasErrors('status');

    expect(Route::find($route->id)->status)->toBe('active');
});

test('changing the status needs its own permission', function () {
    [$user, $company] = routeScenario();

    $route = createRoute($user, $company);

    assignRoleWithPermissions($user, $company, ['routes.list', 'routes.show']);

    switchRouteTo($user, $company, $route, 'inactive')->assertForbidden();
});
