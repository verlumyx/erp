<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

test('the detail shows the transfer with its lines', function () {
    [$user, $company, $origin, , $destination, , $item, $unit] = transferScenario();

    $transfer = createTransfer($user, $company, $origin, $destination, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('transfers.show', ['company' => $company->id, 'id' => $transfer->id]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('transfers/show')
            ->where('transfer.code', 'TRA000001')
            ->where('transfer.origin_warehouse_name', $origin->name)
            ->where('transfer.destination_warehouse_name', $destination->name)
            ->has('transfer.lines', 1));
});

test('a transfer of another company is not found', function () {
    [$user, $company, $origin, , $destination, , $item, $unit] = transferScenario();

    $transfer = createTransfer($user, $company, $origin, $destination, $item, $unit);

    [$stranger, $otherCompany] = createUserWithCompany();

    actingAs($stranger)->withSession(['current_company_id' => $otherCompany->id])
        ->get(route('transfers.show', ['company' => $otherCompany->id, 'id' => $transfer->id]))
        ->assertNotFound();
});

test('the edit form is only reachable for a draft', function () {
    [$user, $company, $origin, $originLocation, $destination, , $item, $unit] = transferScenario();

    registerInventoryMovement($company, $item, $origin, $originLocation, ['quantity' => 10, 'unitCost' => 30]);

    $transfer = createTransfer($user, $company, $origin, $destination, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('transfers.edit', ['company' => $company->id, 'id' => $transfer->id]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('transfers/edit')
            ->has('options.warehouses')
            ->has('options.locations'));
});

test('the detail needs the show permission', function () {
    [$user, $company, $origin, , $destination, , $item, $unit] = transferScenario();

    $transfer = createTransfer($user, $company, $origin, $destination, $item, $unit);

    restrictPermissions($user, $company, ['transfers.list']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('transfers.show', ['company' => $company->id, 'id' => $transfer->id]))
        ->assertForbidden();
});
