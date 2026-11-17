<?php

declare(strict_types=1);

use App\Modules\Client\Models\Client;
use App\Modules\Dispatch\Models\Dispatch;
use App\Modules\Route\Models\RouteClient;
use App\Modules\SalesInvoice\Models\SalesInvoice;
use App\Modules\SalesOrder\Models\SalesOrder;

use function Pest\Laravel\actingAs;

test('it lists pending dispatches filtered by route_id', function () {
    [$user, $company, $warehouse, $routeA, $vehicle] = loadPlanScenario();
    $routeB = createRoute($user, $company, ['name' => 'Ruta B']);

    $dispatchA = Dispatch::factory()->create([
        'company_id' => $company->id,
        'warehouse_id' => $warehouse->id,
        'route_id' => $routeA->id,
        'status' => 'pending',
    ]);

    $dispatchB = Dispatch::factory()->create([
        'company_id' => $company->id,
        'warehouse_id' => $warehouse->id,
        'route_id' => $routeB->id,
        'status' => 'pending',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->getJson(route('load-plans.pending-dispatches', [
            'company' => $company->id,
            'route_id' => $routeA->id,
        ]));

    $response->assertOk();
    $data = $response->json('data');

    expect(count($data))->toBe(1);
    expect($data[0]['id'])->toBe($dispatchA->id);
});

test('it lists pending dispatches of clients assigned to the route', function () {
    [$user, $company, $warehouse, $route, $vehicle] = loadPlanScenario();

    $clientA = Client::factory()->create(['company_id' => $company->id]);
    $clientB = Client::factory()->create(['company_id' => $company->id]);

    // Assign clientA to route
    RouteClient::create([
        'company_id' => $company->id,
        'route_id' => $route->id,
        'client_id' => $clientA->id,
        'sequence' => 1,
        'status' => 'active',
    ]);

    // Dispatch for clientA without route_id
    $dispatchA = Dispatch::factory()->create([
        'company_id' => $company->id,
        'warehouse_id' => $warehouse->id,
        'recipient_type' => 'client',
        'recipient_id' => $clientA->id,
        'route_id' => null,
        'status' => 'pending',
    ]);

    // Dispatch for clientB not in route
    $dispatchB = Dispatch::factory()->create([
        'company_id' => $company->id,
        'warehouse_id' => $warehouse->id,
        'recipient_type' => 'client',
        'recipient_id' => $clientB->id,
        'route_id' => null,
        'status' => 'pending',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->getJson(route('load-plans.pending-dispatches', [
            'company' => $company->id,
            'route_id' => $route->id,
        ]));

    $response->assertOk();
    $data = $response->json('data');

    expect(count($data))->toBe(1);
    expect($data[0]['id'])->toBe($dispatchA->id);
});

test('it excludes dispatches already assigned to another active load plan', function () {
    [$user, $company, $warehouse, $route, $vehicle] = loadPlanScenario();

    $dispatchA = Dispatch::factory()->create([
        'company_id' => $company->id,
        'warehouse_id' => $warehouse->id,
        'route_id' => $route->id,
        'status' => 'pending',
    ]);

    $dispatchB = Dispatch::factory()->create([
        'company_id' => $company->id,
        'warehouse_id' => $warehouse->id,
        'route_id' => $route->id,
        'status' => 'pending',
    ]);

    // Assign dispatchA to plan 1
    createLoadPlan($user, $company, $route, $warehouse, $vehicle, [
        'dispatches' => [
            ['dispatch_id' => $dispatchA->id, 'sequence' => 1],
        ],
    ]);

    // Query pending dispatches without exclusion
    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->getJson(route('load-plans.pending-dispatches', [
            'company' => $company->id,
            'route_id' => $route->id,
        ]));

    $response->assertOk();
    $data = $response->json('data');

    // Only dispatchB is available
    expect(count($data))->toBe(1);
    expect($data[0]['id'])->toBe($dispatchB->id);
});

test('it includes dispatches assigned to current plan when exclude_plan_id is provided', function () {
    [$user, $company, $warehouse, $route, $vehicle] = loadPlanScenario();

    $dispatchA = Dispatch::factory()->create([
        'company_id' => $company->id,
        'warehouse_id' => $warehouse->id,
        'route_id' => $route->id,
        'status' => 'pending',
    ]);

    $plan = createLoadPlan($user, $company, $route, $warehouse, $vehicle, [
        'dispatches' => [
            ['dispatch_id' => $dispatchA->id, 'sequence' => 1],
        ],
    ]);

    // Query pending dispatches with exclude_plan_id = plan->id
    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->getJson(route('load-plans.pending-dispatches', [
            'company' => $company->id,
            'route_id' => $route->id,
            'exclude_plan_id' => $plan->id,
        ]));

    $response->assertOk();
    $data = $response->json('data');

    expect(count($data))->toBe(1);
    expect($data[0]['id'])->toBe($dispatchA->id);
});

test('it resolves linked sales invoice code and sales order code', function () {
    [$user, $company, $warehouse, $route, $vehicle] = loadPlanScenario();

    $order = SalesOrder::factory()->create([
        'company_id' => $company->id,
        'code' => 'PED-999',
    ]);

    $dispatch = Dispatch::factory()->create([
        'company_id' => $company->id,
        'warehouse_id' => $warehouse->id,
        'route_id' => $route->id,
        'sourceable_type' => 'sales_order',
        'sourceable_id' => $order->id,
        'status' => 'pending',
    ]);

    $invoice = SalesInvoice::factory()->create([
        'company_id' => $company->id,
        'code' => 'FAC-777',
        'dispatch_id' => $dispatch->id,
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->getJson(route('load-plans.pending-dispatches', [
            'company' => $company->id,
            'route_id' => $route->id,
        ]));

    $response->assertOk();
    $data = $response->json('data');

    expect(count($data))->toBe(1);
    expect($data[0]['id'])->toBe($dispatch->id);
    expect($data[0]['order_code'])->toBe('PED-999');
    expect($data[0]['invoice_code'])->toBe('FAC-777');
});
