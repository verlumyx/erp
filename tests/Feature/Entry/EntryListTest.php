<?php

declare(strict_types=1);

use App\Modules\Supplier\Models\Supplier;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

test('the list shows the entries of the active company', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = entryScenario();

    createEntry($user, $company, $supplier, $warehouse, $item, $unit);
    createEntry($user, $company, $supplier, $warehouse, $item, $unit);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('entries.index', ['company' => $company->id]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('entries/index')
            ->has('entries', 2)
            ->where('meta.total', 2)
        );
});

test('the list filters by supplier, type and status', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = entryScenario();

    $other = Supplier::factory()->create(['company_id' => $company->id]);

    createEntry($user, $company, $supplier, $warehouse, $item, $unit);
    createEntry($user, $company, $other, $warehouse, $item, $unit, ['supplier_id' => $other->id]);
    createEntry($user, $company, $supplier, $warehouse, $item, $unit, [
        'entry_type' => 'donation',
        'supplier_id' => null,
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('entries.index', ['company' => $company->id, 'supplier_id' => $other->id]))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('entries', 1));

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('entries.index', ['company' => $company->id, 'entry_type' => 'donation']))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('entries', 1));

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('entries.index', ['company' => $company->id, 'status' => 'draft']))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('entries', 3));
});

test('the list does not show entries of another company', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = entryScenario();
    [$otherUser, $otherCompany] = createUserWithCompany();

    createEntry($user, $company, $supplier, $warehouse, $item, $unit);

    actingAs($otherUser)
        ->withSession(['current_company_id' => $otherCompany->id])
        ->get(route('entries.index', ['company' => $otherCompany->id]))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('entries', 0));
});

test('the list needs the permission', function () {
    [$user, $company] = entryScenario();

    assignRoleWithPermissions($user, $company, []);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('entries.index', ['company' => $company->id]))
        ->assertForbidden();
});

test('the detail screen shows the entry with its lines', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit, $location] = entryScenario();

    $entry = createEntry($user, $company, $supplier, $warehouse, $item, $unit, [
        'lines' => [[
            'item_id' => $item->id,
            'measurement_unit_id' => $unit->id,
            'quantity' => 10,
            'unit_price' => 25,
            'location_id' => $location->id,
        ]],
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('entries.show', ['company' => $company->id, 'id' => $entry->id]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('entries/show')
            ->where('entry.code', 'ENT000001')
            ->where('entry.supplier_name', $supplier->name)
            ->where('entry.warehouse_name', $warehouse->name)
            ->has('entry.lines', 1)
            ->where('entry.lines.0.location_name', $location->name)
        );
});

test('the code is sequential per company', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = entryScenario();
    [$otherUser, $otherCompany] = createUserWithCompany();

    $first = createEntry($user, $company, $supplier, $warehouse, $item, $unit);
    $second = createEntry($user, $company, $supplier, $warehouse, $item, $unit);

    expect($first->code)->toBe('ENT000001');
    expect($second->code)->toBe('ENT000002');

    /** Cada empresa lleva su propia serie. */
    [$otherSupplier, $otherWarehouse, $otherItem, $otherUnit] = [
        \App\Modules\Supplier\Models\Supplier::factory()->create(['company_id' => $otherCompany->id]),
        \App\Modules\Warehouse\Models\Warehouse::factory()->create(['company_id' => $otherCompany->id]),
        \App\Modules\Item\Models\Item::factory()->create(['company_id' => $otherCompany->id, 'is_purchasable' => 'yes']),
        \App\Modules\MeasurementUnit\Models\MeasurementUnit::factory()->create(['company_id' => $otherCompany->id]),
    ];

    \App\Modules\Item\Models\ItemUnit::factory()->base()->create([
        'company_id' => $otherCompany->id,
        'item_id' => $otherItem->id,
        'measurement_unit_id' => $otherUnit->id,
    ]);

    todayExchangeRate($otherCompany, $otherUser);

    $third = createEntry($otherUser, $otherCompany, $otherSupplier, $otherWarehouse, $otherItem, $otherUnit);

    expect($third->code)->toBe('ENT000001');
});
