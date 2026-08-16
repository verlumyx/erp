<?php

declare(strict_types=1);

use App\Modules\Warehouse\Models\Warehouse;
use App\Modules\WarehouseLocation\Models\WarehouseLocation;

use function Pest\Laravel\actingAs;

test('the locations index renders with locations', function () {
    [$user, $company] = createUserWithCompany();

    $warehouse = Warehouse::factory()->usesLocations()->create(['company_id' => $company->id]);
    WarehouseLocation::factory()->count(3)->create([
        'warehouse_id' => $warehouse->id,
        'company_id' => $company->id,
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('warehouse-locations.index', ['company' => $company->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('warehouse-locations/index')
        ->has('locations', 3)
        ->where('meta.total', 3)
    );
});

test('locations of warehouses that do not use locations are hidden', function () {
    [$user, $company] = createUserWithCompany();

    $managed = Warehouse::factory()->usesLocations()->create(['company_id' => $company->id]);
    $simple = Warehouse::factory()->create(['company_id' => $company->id, 'uses_locations' => 'no']);

    $visible = WarehouseLocation::factory()->create([
        'warehouse_id' => $managed->id,
        'company_id' => $company->id,
    ]);
    WarehouseLocation::factory()->default()->create([
        'warehouse_id' => $simple->id,
        'company_id' => $company->id,
        'name' => 'Principal',
        'location_code' => 'PRINCIPAL',
        'type' => 'zone',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('warehouse-locations.index', ['company' => $company->id]));

    $response->assertInertia(fn ($page) => $page
        ->has('locations', 1)
        ->where('locations.0.id', $visible->id)
    );
});

test('locations can be filtered by warehouse', function () {
    [$user, $company] = createUserWithCompany();

    $first = Warehouse::factory()->usesLocations()->create(['company_id' => $company->id]);
    $second = Warehouse::factory()->usesLocations()->create(['company_id' => $company->id]);

    $target = WarehouseLocation::factory()->create(['warehouse_id' => $first->id, 'company_id' => $company->id]);
    WarehouseLocation::factory()->create(['warehouse_id' => $second->id, 'company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('warehouse-locations.index', [
            'company' => $company->id,
            'warehouse_id' => $first->id,
        ]));

    $response->assertInertia(fn ($page) => $page
        ->has('locations', 1)
        ->where('locations.0.id', $target->id)
    );
});

test('locations can be filtered by name, location code and type', function () {
    [$user, $company] = createUserWithCompany();

    $warehouse = Warehouse::factory()->usesLocations()->create(['company_id' => $company->id]);

    $target = WarehouseLocation::factory()->create([
        'warehouse_id' => $warehouse->id,
        'company_id' => $company->id,
        'name' => 'Pasillo norte',
        'location_code' => 'N-01-01',
        'type' => 'aisle',
    ]);
    WarehouseLocation::factory()->create([
        'warehouse_id' => $warehouse->id,
        'company_id' => $company->id,
        'name' => 'Estante sur',
        'location_code' => 'S-02-02',
        'type' => 'shelf',
    ]);

    foreach ([['name' => 'norte'], ['location_code' => 'N-01'], ['type' => 'aisle']] as $filter) {
        actingAs($user)
            ->withSession(['current_company_id' => $company->id])
            ->get(route('warehouse-locations.index', ['company' => $company->id, ...$filter]))
            ->assertInertia(fn ($page) => $page
                ->has('locations', 1)
                ->where('locations.0.id', $target->id)
            );
    }
});

test('locations can be filtered by status', function () {
    [$user, $company] = createUserWithCompany();

    $warehouse = Warehouse::factory()->usesLocations()->create(['company_id' => $company->id]);
    WarehouseLocation::factory()->create(['warehouse_id' => $warehouse->id, 'company_id' => $company->id]);
    WarehouseLocation::factory()->inactive()->create(['warehouse_id' => $warehouse->id, 'company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('warehouse-locations.index', ['company' => $company->id, 'status' => 'inactive']));

    $response->assertInertia(fn ($page) => $page->has('locations', 1));
});

test('the index only shows locations from the active company', function () {
    [$user, $company] = createUserWithCompany();

    $warehouse = Warehouse::factory()->usesLocations()->create(['company_id' => $company->id]);
    WarehouseLocation::factory()->count(2)->create([
        'warehouse_id' => $warehouse->id,
        'company_id' => $company->id,
    ]);
    WarehouseLocation::factory()->count(3)->create();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('warehouse-locations.index', ['company' => $company->id]));

    $response->assertInertia(fn ($page) => $page
        ->has('locations', 2)
        ->where('meta.total', 2)
    );
});

test('a user without permission cannot list locations', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, []);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('warehouse-locations.index', ['company' => $company->id]));

    $response->assertForbidden();
});
