<?php

declare(strict_types=1);

use App\Modules\Warehouse\Models\Warehouse;
use App\Modules\WarehouseLocation\Models\WarehouseLocation;

use function Pest\Laravel\actingAs;

test('a location can be updated', function () {
    [$user, $company] = createUserWithCompany();

    $warehouse = Warehouse::factory()->usesLocations()->create(['company_id' => $company->id]);
    $location = WarehouseLocation::factory()->create([
        'warehouse_id' => $warehouse->id,
        'company_id' => $company->id,
        'name' => 'Nombre viejo',
        'location_code' => 'A-01-01',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('warehouse-locations.update', ['company' => $company->id, 'id' => $location->id]), [
            'name' => 'Nombre nuevo',
            'location_code' => 'B-02-02',
            'type' => 'bin',
            'capacity' => 50,
            'is_default' => 'no',
        ]);

    $response->assertRedirect(route('warehouse-locations.show', ['company' => $company->id, 'id' => $location->id]));
    $response->assertSessionHasNoErrors();

    $location->refresh();
    expect($location->name)->toBe('Nombre nuevo');
    expect($location->location_code)->toBe('B-02-02');
    expect($location->type)->toBe('bin');
    expect((float) $location->capacity)->toBe(50.0);
});

test('promoting a location to default demotes the previous one', function () {
    [$user, $company] = createUserWithCompany();

    $warehouse = Warehouse::factory()->usesLocations()->create(['company_id' => $company->id]);
    $previous = WarehouseLocation::factory()->default()->create([
        'warehouse_id' => $warehouse->id,
        'company_id' => $company->id,
    ]);
    $location = WarehouseLocation::factory()->create([
        'warehouse_id' => $warehouse->id,
        'company_id' => $company->id,
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('warehouse-locations.update', ['company' => $company->id, 'id' => $location->id]), [
            'name' => $location->name,
            'location_code' => $location->location_code,
            'type' => $location->type,
            'is_default' => 'yes',
        ]);

    expect($location->fresh()->is_default)->toBe('yes');
    expect($previous->fresh()->is_default)->toBe('no');
});

test('the default location of a warehouse without locations cannot be edited', function () {
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
        ->put(route('warehouse-locations.update', ['company' => $company->id, 'id' => $location->id]), [
            'name' => 'Renombrada',
            'location_code' => 'PRINCIPAL',
            'type' => 'zone',
        ]);

    $response->assertSessionHasErrors('name');
    expect($location->fresh()->name)->toBe('Principal');
});

test('a location cannot become its own parent', function () {
    [$user, $company] = createUserWithCompany();

    $warehouse = Warehouse::factory()->usesLocations()->create(['company_id' => $company->id]);
    $location = WarehouseLocation::factory()->create([
        'warehouse_id' => $warehouse->id,
        'company_id' => $company->id,
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('warehouse-locations.update', ['company' => $company->id, 'id' => $location->id]), [
            'name' => $location->name,
            'location_code' => $location->location_code,
            'type' => $location->type,
            'parent_id' => $location->id,
        ]);

    $response->assertSessionHasErrors('parent_id');
});

test('the location code stays unique within the warehouse on update', function () {
    [$user, $company] = createUserWithCompany();

    $warehouse = Warehouse::factory()->usesLocations()->create(['company_id' => $company->id]);
    WarehouseLocation::factory()->create([
        'warehouse_id' => $warehouse->id,
        'company_id' => $company->id,
        'location_code' => 'A-01-01',
    ]);
    $location = WarehouseLocation::factory()->create([
        'warehouse_id' => $warehouse->id,
        'company_id' => $company->id,
        'location_code' => 'B-02-02',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('warehouse-locations.update', ['company' => $company->id, 'id' => $location->id]), [
            'name' => $location->name,
            'location_code' => 'A-01-01',
            'type' => $location->type,
        ]);

    $response->assertSessionHasErrors('location_code');
});

test('a user without permission cannot update a location', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['warehouse-locations.list']);

    $warehouse = Warehouse::factory()->usesLocations()->create(['company_id' => $company->id]);
    $location = WarehouseLocation::factory()->create([
        'warehouse_id' => $warehouse->id,
        'company_id' => $company->id,
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('warehouse-locations.update', ['company' => $company->id, 'id' => $location->id]), [
            'name' => 'Prohibida',
            'location_code' => 'X-01',
            'type' => 'shelf',
        ]);

    $response->assertForbidden();
});
