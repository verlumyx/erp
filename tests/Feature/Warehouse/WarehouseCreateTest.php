<?php

declare(strict_types=1);

use App\Modules\Warehouse\Models\Warehouse;
use App\Modules\WarehouseLocation\Models\WarehouseLocation;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

test('a warehouse can be created', function () {
    [$user, $company] = createUserWithCompany();

    $id = (string) Str::uuid7();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('warehouses.store', ['company' => $company->id]), [
            'id' => $id,
            'name' => 'Bodega Central',
            'type' => 'main',
            'address' => 'Av. Principal 123',
            'phone' => '04141234567',
            'city' => 'Caracas',
            'is_default' => 'yes',
            'allows_negative_stock' => 'no',
            'uses_locations' => 'no',
            'is_sales_available' => 'yes',
            'notes' => 'Bodega principal de la empresa',
        ]);

    $response->assertRedirect(route('warehouses.index', ['company' => $company->id]));
    $response->assertSessionHasNoErrors();

    $warehouse = Warehouse::find($id);
    expect($warehouse)->not->toBeNull();
    expect($warehouse->name)->toBe('Bodega Central');
    expect($warehouse->type)->toBe('main');
    expect($warehouse->city)->toBe('Caracas');
    expect($warehouse->is_default)->toBe('yes');
    expect($warehouse->status)->toBe('active');
    expect($warehouse->company_id)->toBe($company->id);
    expect($warehouse->created_by)->toBe($user->id);
    expect($warehouse->code)->toBe('BOD000001');
});

test('creating a warehouse also creates its default "Principal" location', function () {
    [$user, $company] = createUserWithCompany();

    $id = (string) Str::uuid7();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('warehouses.store', ['company' => $company->id]), [
            'id' => $id,
            'name' => 'Bodega Central',
            'type' => 'main',
        ]);

    $location = WarehouseLocation::where('warehouse_id', $id)->first();

    expect($location)->not->toBeNull();
    expect($location->name)->toBe('Principal');
    expect($location->location_code)->toBe('PRINCIPAL');
    expect($location->type)->toBe('zone');
    expect($location->is_default)->toBe('yes');
    expect($location->parent_id)->toBeNull();
    expect($location->company_id)->toBe($company->id);
    expect($location->code)->toBe('UBI000001');
});

test('the code auto-increments per company', function () {
    [$user, $company] = createUserWithCompany();

    $first = (string) Str::uuid7();
    $second = (string) Str::uuid7();

    foreach ([[$first, 'Bodega Uno'], [$second, 'Bodega Dos']] as [$id, $name]) {
        actingAs($user)
            ->withSession(['current_company_id' => $company->id])
            ->post(route('warehouses.store', ['company' => $company->id]), [
                'id' => $id,
                'name' => $name,
                'type' => 'main',
            ]);
    }

    expect(Warehouse::find($first)->code)->toBe('BOD000001');
    expect(Warehouse::find($second)->code)->toBe('BOD000002');
});

test('each company has its own code sequence', function () {
    [$userA, $companyA] = createUserWithCompany();
    [$userB, $companyB] = createUserWithCompany();

    $idA = (string) Str::uuid7();
    $idB = (string) Str::uuid7();

    actingAs($userA)
        ->withSession(['current_company_id' => $companyA->id])
        ->post(route('warehouses.store', ['company' => $companyA->id]), [
            'id' => $idA,
            'name' => 'Bodega A',
            'type' => 'main',
        ]);

    actingAs($userB)
        ->withSession(['current_company_id' => $companyB->id])
        ->post(route('warehouses.store', ['company' => $companyB->id]), [
            'id' => $idB,
            'name' => 'Bodega B',
            'type' => 'main',
        ]);

    expect(Warehouse::find($idA)->code)->toBe('BOD000001');
    expect(Warehouse::find($idB)->code)->toBe('BOD000001');
});

test('only one warehouse stays default per company', function () {
    [$user, $company] = createUserWithCompany();

    $previous = Warehouse::factory()->default()->create(['company_id' => $company->id]);

    $id = (string) Str::uuid7();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('warehouses.store', ['company' => $company->id]), [
            'id' => $id,
            'name' => 'Nueva por defecto',
            'type' => 'main',
            'is_default' => 'yes',
        ]);

    expect(Warehouse::find($id)->is_default)->toBe('yes');
    expect($previous->fresh()->is_default)->toBe('no');
});

test('the name is required', function () {
    [$user, $company] = createUserWithCompany();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('warehouses.store', ['company' => $company->id]), [
            'id' => (string) Str::uuid7(),
            'name' => '',
            'type' => 'main',
        ]);

    $response->assertSessionHasErrors('name');
});

test('the name must be unique within the company', function () {
    [$user, $company] = createUserWithCompany();

    Warehouse::factory()->create(['company_id' => $company->id, 'name' => 'Bodega Central']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('warehouses.store', ['company' => $company->id]), [
            'id' => (string) Str::uuid7(),
            'name' => 'Bodega Central',
            'type' => 'main',
        ]);

    $response->assertSessionHasErrors('name');
});

test('the type must be one of the allowed values', function () {
    [$user, $company] = createUserWithCompany();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('warehouses.store', ['company' => $company->id]), [
            'id' => (string) Str::uuid7(),
            'name' => 'Bodega inválida',
            'type' => 'warehouse',
        ]);

    $response->assertSessionHasErrors('type');
});

test('the responsible user must belong to the company', function () {
    [$user, $company] = createUserWithCompany();
    [$otherUser] = createUserWithCompany();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('warehouses.store', ['company' => $company->id]), [
            'id' => (string) Str::uuid7(),
            'name' => 'Bodega con encargado ajeno',
            'type' => 'main',
            'responsible_user_id' => $otherUser->id,
        ]);

    $response->assertSessionHasErrors('responsible_user_id');
});

test('a user without permission cannot create a warehouse', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['warehouses.list']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('warehouses.store', ['company' => $company->id]), [
            'id' => (string) Str::uuid7(),
            'name' => 'Prohibida',
            'type' => 'main',
        ]);

    $response->assertForbidden();
});
