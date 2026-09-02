<?php

declare(strict_types=1);

use App\Modules\Client\Models\Client;
use App\Modules\Dispatch\Models\Dispatch;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

test('the list shows the dispatches of the active company', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = dispatchScenario();

    createDispatch($user, $company, $client, $warehouse, $item, $unit);
    createDispatch($user, $company, $client, $warehouse, $item, $unit);

    /** Un despacho de otra empresa no se cuela en el listado. */
    [$stranger, $otherCompany] = createUserWithCompany();
    Dispatch::factory()->create([
        'company_id' => $otherCompany->id,
        'recipient_id' => Client::factory()->create(['company_id' => $otherCompany->id])->id,
        'warehouse_id' => \App\Modules\Warehouse\Models\Warehouse::factory()
            ->create(['company_id' => $otherCompany->id])->id,
        'created_by' => $stranger->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('dispatches.index', ['company' => $company->id]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('dispatches/index')
            ->has('dispatches', 2)
            ->where('meta.total', 2));
});

test('the list filters by code', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = dispatchScenario();

    createDispatch($user, $company, $client, $warehouse, $item, $unit);
    createDispatch($user, $company, $client, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('dispatches.index', ['company' => $company->id, 'code' => 'DES000002']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('dispatches', 1)
            ->where('dispatches.0.code', 'DES000002'));
});

test('the list filters by delivery result', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = dispatchScenario();

    $first = createDispatch($user, $company, $client, $warehouse, $item, $unit);
    createDispatch($user, $company, $client, $warehouse, $item, $unit);

    Dispatch::where('id', $first->id)->update(['delivery_status' => 'rejected']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('dispatches.index', ['company' => $company->id, 'delivery_status' => 'rejected']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->has('dispatches', 1));
});

test('the list filters by client', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = dispatchScenario();

    $other = Client::factory()->create(['company_id' => $company->id]);

    createDispatch($user, $company, $client, $warehouse, $item, $unit);
    createDispatch($user, $company, $other, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('dispatches.index', ['company' => $company->id, 'recipient_id' => $other->id]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('dispatches', 1)
            ->where('dispatches.0.recipient_id', $other->id));
});

test('the list requires the list permission', function () {
    [$user, $company] = dispatchScenario();

    assignRoleWithPermissions($user, $company, ['dispatches.show']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('dispatches.index', ['company' => $company->id]))
        ->assertForbidden();
});
