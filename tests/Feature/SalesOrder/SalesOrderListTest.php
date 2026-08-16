<?php

declare(strict_types=1);

use App\Modules\Client\Models\Client;
use App\Modules\SalesOrder\Models\SalesOrder;
use App\Modules\Warehouse\Models\Warehouse;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

test('the sales order list is rendered', function () {
    [$user, $company, $client, $warehouse] = salesOrderScenario();

    SalesOrder::factory()->count(3)->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'warehouse_id' => $warehouse->id,
        'created_by' => $user->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('sales-orders.index', ['company' => $company->id]))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('sales-orders/index')
                ->has('salesOrders', 3)
                ->where('meta.total', 3)
                ->has('options.clients')
                ->has('options.warehouses')
                ->has('options.items'),
        );
});

test('the list only shows orders of the active company', function () {
    [$user, $company, $client, $warehouse] = salesOrderScenario();

    SalesOrder::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'warehouse_id' => $warehouse->id,
    ]);

    /** Pedido de otra empresa: no debe aparecer. */
    SalesOrder::factory()->create();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('sales-orders.index', ['company' => $company->id]))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('salesOrders', 1));
});

test('the list can be filtered by status', function () {
    [$user, $company, $client, $warehouse] = salesOrderScenario();

    SalesOrder::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'warehouse_id' => $warehouse->id,
    ]);
    SalesOrder::factory()->confirmed()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'warehouse_id' => $warehouse->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('sales-orders.index', ['company' => $company->id, 'status' => 'confirmed']))
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->has('salesOrders', 1)
                ->where('salesOrders.0.status', 'confirmed'),
        );
});

test('the list can be filtered by client name', function () {
    [$user, $company, $client, $warehouse] = salesOrderScenario();

    $other = Client::factory()->create([
        'company_id' => $company->id,
        'name' => 'Distribuidora Andina',
    ]);

    SalesOrder::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'warehouse_id' => $warehouse->id,
    ]);
    SalesOrder::factory()->create([
        'company_id' => $company->id,
        'client_id' => $other->id,
        'warehouse_id' => $warehouse->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('sales-orders.index', ['company' => $company->id, 'client' => 'Andina']))
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->has('salesOrders', 1)
                ->where('salesOrders.0.client_name', 'Distribuidora Andina'),
        );
});

test('the list can be filtered by warehouse and date range', function () {
    [$user, $company, $client, $warehouse] = salesOrderScenario();

    $otherWarehouse = Warehouse::factory()->create(['company_id' => $company->id]);

    SalesOrder::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'warehouse_id' => $warehouse->id,
        'order_date' => '2026-08-01',
    ]);
    SalesOrder::factory()->create([
        'company_id' => $company->id,
        'client_id' => $client->id,
        'warehouse_id' => $otherWarehouse->id,
        'order_date' => '2026-08-20',
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('sales-orders.index', [
            'company' => $company->id,
            'warehouse_id' => $otherWarehouse->id,
        ]))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('salesOrders', 1));

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('sales-orders.index', [
            'company' => $company->id,
            'order_date_from' => '2026-08-10',
        ]))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('salesOrders', 1));
});

test('a user without permission cannot list sales orders', function () {
    [$user, $company] = salesOrderScenario();
    assignRoleWithPermissions($user, $company, ['clients.list']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('sales-orders.index', ['company' => $company->id]))
        ->assertForbidden();
});
