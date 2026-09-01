<?php

declare(strict_types=1);

use App\Modules\SalesOrder\Models\SalesOrder;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

test('the detail carries the dispatch with its lines', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = dispatchScenario();

    $dispatch = createDispatch($user, $company, $client, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('dispatches.show', ['company' => $company->id, 'id' => $dispatch->id]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('dispatches/show')
            ->where('dispatch.code', 'DES000001')
            ->where('dispatch.client_name', $client->name)
            ->where('dispatch.warehouse_name', $warehouse->name)
            ->where('dispatch.delivery_status', 'pending')
            ->has('dispatch.lines', 1)
            ->where('dispatch.lines.0.item_name', $item->name));
});

test('the detail names the source order', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = dispatchScenario();

    $order = createSalesOrder($user, $company, $client, $warehouse, $item, $unit);

    $dispatch = createDispatch($user, $company, $client, $warehouse, $item, $unit, [
        'sourceable_type' => SalesOrder::MORPH_ALIAS,
        'sourceable_id' => $order->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('dispatches.show', ['company' => $company->id, 'id' => $dispatch->id]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('dispatch.sourceable_type', 'sales_order')
            ->where('dispatch.sourceable_code', $order->code));
});

test('the edit screen carries the dispatch and the catalogs', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = dispatchScenario();

    $dispatch = createDispatch($user, $company, $client, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('dispatches.edit', ['company' => $company->id, 'id' => $dispatch->id]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('dispatches/edit')
            ->where('dispatch.id', $dispatch->id)
            ->has('options.warehouses'));
});

test('the detail requires the show permission', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = dispatchScenario();

    $dispatch = createDispatch($user, $company, $client, $warehouse, $item, $unit);

    assignRoleWithPermissions($user, $company, ['dispatches.list']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('dispatches.show', ['company' => $company->id, 'id' => $dispatch->id]))
        ->assertForbidden();
});
