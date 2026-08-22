<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

test('a sales order detail is rendered with its lines', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    $order = createSalesOrder($user, $company, $client, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('sales-orders.show', ['company' => $company->id, 'id' => $order->id]))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('sales-orders/show')
                ->where('salesOrder.id', $order->id)
                ->where('salesOrder.code', 'OVE000001')
                ->where('salesOrder.status', 'draft')
                ->where('salesOrder.client_name', $client->name)
                ->where('salesOrder.warehouse_name', $warehouse->name)
                ->has('salesOrder.lines', 1)
                ->where('salesOrder.lines.0.item_name', $item->name),
        );
});

test('the edit form is rendered for a draft order', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    $order = createSalesOrder($user, $company, $client, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('sales-orders.edit', ['company' => $company->id, 'id' => $order->id]))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('sales-orders/edit')
                ->where('salesOrder.id', $order->id)
                ->missing('options.items'),
        );
});

test('the create form is rendered with its catalogs', function () {
    [$user, $company] = salesOrderScenario();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('sales-orders.create', ['company' => $company->id]))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('sales-orders/create')
                ->has('options.warehouses', 1)
                /* Ni los artículos ni los clientes viajan: se buscan contra su lookup. */
                ->missing('options.items')
                ->missing('options.clients'),
        );
});

test('an order of another company is not reachable', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();
    [, $otherCompany] = createUserWithCompany();

    $order = createSalesOrder($user, $company, $client, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $otherCompany->id])
        ->get(route('sales-orders.show', ['company' => $otherCompany->id, 'id' => $order->id]))
        ->assertForbidden();
});

test('a user without permission cannot see the detail', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    $order = createSalesOrder($user, $company, $client, $warehouse, $item, $unit);

    assignRoleWithPermissions($user, $company, ['sales-orders.list']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('sales-orders.show', ['company' => $company->id, 'id' => $order->id]))
        ->assertForbidden();
});

test('a missing sales order returns a not found response', function () {
    [$user, $company] = salesOrderScenario();

    /** `SalesOrderNotFoundException` la traduce a 404 el handler de bootstrap/app.php. */
    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('sales-orders.show', [
            'company' => $company->id,
            'id' => (string) Str::uuid7(),
        ]))
        ->assertNotFound();
});
