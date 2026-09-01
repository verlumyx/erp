<?php

declare(strict_types=1);

use App\Modules\Route\Models\RouteStop;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

test('the detail shows the route with its fixed clients', function () {
    [$user, $company, $client, $warehouse] = routeScenario();

    $route = createRoute($user, $company, [
        'warehouse_id' => $warehouse->id,
        'clients' => [['client_id' => $client->id]],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('routes.show', ['company' => $company->id, 'id' => $route->id]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('routes/show')
            ->where('route.code', 'RUT000001')
            ->where('route.warehouse_name', $warehouse->name)
            ->has('route.clients', 1)
            ->where('route.clients.0.client_name', $client->name)
            /** Sin fecha en la query se enseña el recorrido de hoy. */
            ->where('stop_date', now()->toDateString())
            ->has('stops', 0));
});

test('the detail shows the stops of the day asked for', function () {
    [$user, $company, $client] = routeScenario();

    $route = createRoute($user, $company);
    $tomorrow = now()->addDay()->toDateString();

    RouteStop::factory()->create([
        'company_id' => $company->id,
        'route_id' => $route->id,
        'client_id' => $client->id,
        'stop_date' => $tomorrow,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('routes.show', [
            'company' => $company->id,
            'id' => $route->id,
            'stop_date' => $tomorrow,
        ]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('stop_date', $tomorrow)
            ->has('stops', 1)
            ->where('stops.0.client_name', $client->name)
            ->where('stops.0.stop_status', 'pending'));

    /** El mismo recorrido, mirado hoy, todavía no tiene nada. */
    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('routes.show', ['company' => $company->id, 'id' => $route->id]))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('stops', 0));
});

test('a route of another company is not reachable', function () {
    [$user, $company] = routeScenario();
    [$stranger, $otherCompany] = createUserWithCompany();

    $route = createRoute($stranger, $otherCompany);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('routes.show', ['company' => $company->id, 'id' => $route->id]))
        ->assertNotFound();
});
