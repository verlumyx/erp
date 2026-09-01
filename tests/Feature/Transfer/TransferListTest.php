<?php

declare(strict_types=1);

use App\Modules\Transfer\Models\Transfer;
use App\Modules\Warehouse\Models\Warehouse;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

test('the list shows the transfers of the active company', function () {
    [$user, $company, $origin, , $destination, , $item, $unit] = transferScenario();

    createTransfer($user, $company, $origin, $destination, $item, $unit);
    createTransfer($user, $company, $origin, $destination, $item, $unit);

    /** Un traslado de otra empresa no se cuela en el listado. */
    [$stranger, $otherCompany] = createUserWithCompany();
    Transfer::factory()->create([
        'company_id' => $otherCompany->id,
        'origin_warehouse_id' => Warehouse::factory()->create(['company_id' => $otherCompany->id])->id,
        'destination_warehouse_id' => Warehouse::factory()->create(['company_id' => $otherCompany->id])->id,
        'created_by' => $stranger->id,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('transfers.index', ['company' => $company->id]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('transfers/index')
            ->has('transfers', 2)
            ->has('options.warehouses')
            ->where('meta.total', 2));
});

test('the list filters by code', function () {
    [$user, $company, $origin, , $destination, , $item, $unit] = transferScenario();

    createTransfer($user, $company, $origin, $destination, $item, $unit);
    createTransfer($user, $company, $origin, $destination, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('transfers.index', ['company' => $company->id, 'code' => 'TRA000002']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('transfers', 1)
            ->where('transfers.0.code', 'TRA000002'));
});

test('the warehouse filter answers both sides of the move', function () {
    [$user, $company, $origin, , $destination, , $item, $unit] = transferScenario();

    [$other] = warehouseWithDefaultLocation($company, $user);

    createTransfer($user, $company, $origin, $destination, $item, $unit);
    createTransfer($user, $company, $origin, $other, $item, $unit);

    /** Lo que se mueve por esa bodega: salga de ella o llegue a ella. */
    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('transfers.index', ['company' => $company->id, 'warehouse_id' => $destination->id]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->has('transfers', 1));

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('transfers.index', ['company' => $company->id, 'warehouse_id' => $origin->id]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->has('transfers', 2));
});

test('the list filters by reason and by status', function () {
    [$user, $company, $origin, , $destination, , $item, $unit] = transferScenario();

    createTransfer($user, $company, $origin, $destination, $item, $unit);
    createTransfer($user, $company, $origin, $destination, $item, $unit, [
        'reason' => 'quarantine',
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('transfers.index', ['company' => $company->id, 'reason' => 'quarantine']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->has('transfers', 1));

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('transfers.index', ['company' => $company->id, 'status' => 'draft']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->has('transfers', 2));
});

test('the list needs the list permission', function () {
    [$user, $company] = transferScenario();

    restrictPermissions($user, $company, ['transfers.show']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('transfers.index', ['company' => $company->id]))
        ->assertForbidden();
});
