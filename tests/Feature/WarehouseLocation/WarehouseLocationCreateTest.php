<?php

declare(strict_types=1);

use App\Modules\Warehouse\Models\Warehouse;
use App\Modules\WarehouseLocation\Models\WarehouseLocation;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

test('a location can be created for a warehouse that uses locations', function () {
    [$user, $company] = createUserWithCompany();

    $warehouse = Warehouse::factory()->usesLocations()->create(['company_id' => $company->id]);

    $id = (string) Str::uuid7();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('warehouse-locations.store', ['company' => $company->id]), [
            'id' => $id,
            'warehouse_id' => $warehouse->id,
            'name' => 'Estante A1',
            'location_code' => 'A-01-03',
            'type' => 'shelf',
            'capacity' => 120.5,
            'is_default' => 'no',
        ]);

    $response->assertRedirect(route('warehouse-locations.index', ['company' => $company->id]));
    $response->assertSessionHasNoErrors();

    $location = WarehouseLocation::find($id);
    expect($location)->not->toBeNull();
    expect($location->name)->toBe('Estante A1');
    expect($location->location_code)->toBe('A-01-03');
    expect($location->type)->toBe('shelf');
    expect((float) $location->capacity)->toBe(120.5);
    expect($location->status)->toBe('active');
    expect($location->company_id)->toBe($company->id);
    expect($location->created_by)->toBe($user->id);
    expect($location->code)->toBe('UBI000001');
});

test('a location can hang from a parent location of the same warehouse', function () {
    [$user, $company] = createUserWithCompany();

    $warehouse = Warehouse::factory()->usesLocations()->create(['company_id' => $company->id]);
    $parent = WarehouseLocation::factory()->create([
        'warehouse_id' => $warehouse->id,
        'company_id' => $company->id,
        'type' => 'zone',
    ]);

    $id = (string) Str::uuid7();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('warehouse-locations.store', ['company' => $company->id]), [
            'id' => $id,
            'warehouse_id' => $warehouse->id,
            'parent_id' => $parent->id,
            'name' => 'Pasillo 1',
            'location_code' => 'P-01',
            'type' => 'aisle',
        ]);

    $response->assertSessionHasNoErrors();
    expect(WarehouseLocation::find($id)->parent_id)->toBe($parent->id);
});

test('a location cannot be created for a warehouse that does not use locations', function () {
    [$user, $company] = createUserWithCompany();

    $warehouse = Warehouse::factory()->create(['company_id' => $company->id, 'uses_locations' => 'no']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('warehouse-locations.store', ['company' => $company->id]), [
            'id' => (string) Str::uuid7(),
            'warehouse_id' => $warehouse->id,
            'name' => 'Estante suelto',
            'location_code' => 'X-01',
            'type' => 'shelf',
        ]);

    $response->assertSessionHasErrors('warehouse_id');
});

test('a location cannot be created for a warehouse of another company', function () {
    [$user, $company] = createUserWithCompany();

    $foreign = Warehouse::factory()->usesLocations()->create();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('warehouse-locations.store', ['company' => $company->id]), [
            'id' => (string) Str::uuid7(),
            'warehouse_id' => $foreign->id,
            'name' => 'Ajena',
            'location_code' => 'X-01',
            'type' => 'shelf',
        ]);

    $response->assertSessionHasErrors('warehouse_id');
});

test('the location code is unique within the warehouse', function () {
    [$user, $company] = createUserWithCompany();

    $warehouse = Warehouse::factory()->usesLocations()->create(['company_id' => $company->id]);
    WarehouseLocation::factory()->create([
        'warehouse_id' => $warehouse->id,
        'company_id' => $company->id,
        'location_code' => 'A-01-03',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('warehouse-locations.store', ['company' => $company->id]), [
            'id' => (string) Str::uuid7(),
            'warehouse_id' => $warehouse->id,
            'name' => 'Duplicada',
            'location_code' => 'A-01-03',
            'type' => 'shelf',
        ]);

    $response->assertSessionHasErrors('location_code');
});

test('another warehouse can reuse the same location code', function () {
    [$user, $company] = createUserWithCompany();

    $first = Warehouse::factory()->usesLocations()->create(['company_id' => $company->id]);
    $second = Warehouse::factory()->usesLocations()->create(['company_id' => $company->id]);

    WarehouseLocation::factory()->create([
        'warehouse_id' => $first->id,
        'company_id' => $company->id,
        'location_code' => 'A-01-03',
    ]);

    $id = (string) Str::uuid7();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('warehouse-locations.store', ['company' => $company->id]), [
            'id' => $id,
            'warehouse_id' => $second->id,
            'name' => 'Misma etiqueta',
            'location_code' => 'A-01-03',
            'type' => 'shelf',
        ]);

    $response->assertSessionHasNoErrors();
    expect(WarehouseLocation::find($id))->not->toBeNull();
});

test('the PRINCIPAL code is reserved', function () {
    [$user, $company] = createUserWithCompany();

    $warehouse = Warehouse::factory()->usesLocations()->create(['company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('warehouse-locations.store', ['company' => $company->id]), [
            'id' => (string) Str::uuid7(),
            'warehouse_id' => $warehouse->id,
            'name' => 'Falsa principal',
            'location_code' => 'principal',
            'type' => 'zone',
        ]);

    $response->assertSessionHasErrors('location_code');
});

test('the parent location must belong to the same warehouse', function () {
    [$user, $company] = createUserWithCompany();

    $warehouse = Warehouse::factory()->usesLocations()->create(['company_id' => $company->id]);
    $other = Warehouse::factory()->usesLocations()->create(['company_id' => $company->id]);
    $parent = WarehouseLocation::factory()->create([
        'warehouse_id' => $other->id,
        'company_id' => $company->id,
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('warehouse-locations.store', ['company' => $company->id]), [
            'id' => (string) Str::uuid7(),
            'warehouse_id' => $warehouse->id,
            'parent_id' => $parent->id,
            'name' => 'Hija equivocada',
            'location_code' => 'H-01',
            'type' => 'shelf',
        ]);

    $response->assertSessionHasErrors('parent_id');
});

test('only one location stays default per warehouse', function () {
    [$user, $company] = createUserWithCompany();

    $warehouse = Warehouse::factory()->usesLocations()->create(['company_id' => $company->id]);
    $previous = WarehouseLocation::factory()->default()->create([
        'warehouse_id' => $warehouse->id,
        'company_id' => $company->id,
    ]);

    $id = (string) Str::uuid7();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('warehouse-locations.store', ['company' => $company->id]), [
            'id' => $id,
            'warehouse_id' => $warehouse->id,
            'name' => 'Nueva por defecto',
            'location_code' => 'N-01',
            'type' => 'shelf',
            'is_default' => 'yes',
        ]);

    expect(WarehouseLocation::find($id)->is_default)->toBe('yes');
    expect($previous->fresh()->is_default)->toBe('no');
});

test('a user without permission cannot create a location', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['warehouse-locations.list']);

    $warehouse = Warehouse::factory()->usesLocations()->create(['company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('warehouse-locations.store', ['company' => $company->id]), [
            'id' => (string) Str::uuid7(),
            'warehouse_id' => $warehouse->id,
            'name' => 'Prohibida',
            'location_code' => 'X-01',
            'type' => 'shelf',
        ]);

    $response->assertForbidden();
});
