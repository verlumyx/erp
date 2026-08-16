<?php

declare(strict_types=1);

use App\Modules\Warehouse\Models\Warehouse;
use App\Modules\WarehouseLocation\Models\WarehouseLocation;

use function Pest\Laravel\actingAs;

test('a location can be deactivated', function () {
    [$user, $company] = createUserWithCompany();

    $warehouse = Warehouse::factory()->usesLocations()->create(['company_id' => $company->id]);
    $location = WarehouseLocation::factory()->create([
        'warehouse_id' => $warehouse->id,
        'company_id' => $company->id,
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('warehouse-locations.update-status', ['company' => $company->id, 'id' => $location->id]), [
            'status' => 'inactive',
        ]);

    $response->assertRedirect(route('warehouse-locations.index', ['company' => $company->id]));
    expect($location->fresh()->status)->toBe('inactive');
});

test('the default location cannot be deactivated', function () {
    [$user, $company] = createUserWithCompany();

    $warehouse = Warehouse::factory()->usesLocations()->create(['company_id' => $company->id]);
    $location = WarehouseLocation::factory()->default()->create([
        'warehouse_id' => $warehouse->id,
        'company_id' => $company->id,
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('warehouse-locations.update-status', ['company' => $company->id, 'id' => $location->id]), [
            'status' => 'inactive',
        ]);

    $response->assertSessionHasErrors('status');
    expect($location->fresh()->status)->toBe('active');
});

test('the location of a warehouse without locations cannot be deactivated', function () {
    [$user, $company] = createUserWithCompany();

    $warehouse = Warehouse::factory()->create(['company_id' => $company->id, 'uses_locations' => 'no']);
    $location = WarehouseLocation::factory()->default()->create([
        'warehouse_id' => $warehouse->id,
        'company_id' => $company->id,
        'name' => 'Principal',
        'location_code' => 'PRINCIPAL',
        'type' => 'zone',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('warehouse-locations.update-status', ['company' => $company->id, 'id' => $location->id]), [
            'status' => 'inactive',
        ]);

    $response->assertSessionHasErrors('status');
});

test('a location can be reactivated', function () {
    [$user, $company] = createUserWithCompany();

    $warehouse = Warehouse::factory()->usesLocations()->create(['company_id' => $company->id]);
    $location = WarehouseLocation::factory()->inactive()->create([
        'warehouse_id' => $warehouse->id,
        'company_id' => $company->id,
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('warehouse-locations.update-status', ['company' => $company->id, 'id' => $location->id]), [
            'status' => 'active',
        ]);

    expect($location->fresh()->status)->toBe('active');
});

test('a user without permission cannot change the location status', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['warehouse-locations.list']);

    $warehouse = Warehouse::factory()->usesLocations()->create(['company_id' => $company->id]);
    $location = WarehouseLocation::factory()->create([
        'warehouse_id' => $warehouse->id,
        'company_id' => $company->id,
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('warehouse-locations.update-status', ['company' => $company->id, 'id' => $location->id]), [
            'status' => 'inactive',
        ]);

    $response->assertForbidden();
});
