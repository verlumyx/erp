<?php

declare(strict_types=1);

use App\Modules\Warehouse\Models\Warehouse;

use function Pest\Laravel\actingAs;

test('a warehouse can be updated', function () {
    [$user, $company] = createUserWithCompany();

    $warehouse = Warehouse::factory()->create([
        'company_id' => $company->id,
        'name' => 'Nombre viejo',
        'type' => 'main',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('warehouses.update', ['company' => $company->id, 'id' => $warehouse->id]), [
            'name' => 'Nombre nuevo',
            'type' => 'branch',
            'address' => 'Calle 5',
            'phone' => '02121234567',
            'city' => 'Barquisimeto',
            'responsible_user_id' => $user->id,
            'is_default' => 'no',
            'allows_negative_stock' => 'yes',
            'uses_locations' => 'yes',
            'is_sales_available' => 'no',
            'notes' => 'Actualizada',
        ]);

    $response->assertRedirect(route('warehouses.show', ['company' => $company->id, 'id' => $warehouse->id]));
    $response->assertSessionHasNoErrors();

    $warehouse->refresh();
    expect($warehouse->name)->toBe('Nombre nuevo');
    expect($warehouse->type)->toBe('branch');
    expect($warehouse->city)->toBe('Barquisimeto');
    expect($warehouse->responsible_user_id)->toBe($user->id);
    expect($warehouse->allows_negative_stock)->toBe('yes');
    expect($warehouse->uses_locations)->toBe('yes');
    expect($warehouse->is_sales_available)->toBe('no');
});

test('promoting a warehouse to default demotes the previous one', function () {
    [$user, $company] = createUserWithCompany();

    $previous = Warehouse::factory()->default()->create(['company_id' => $company->id]);
    $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('warehouses.update', ['company' => $company->id, 'id' => $warehouse->id]), [
            'name' => $warehouse->name,
            'type' => 'main',
            'is_default' => 'yes',
        ]);

    expect($warehouse->fresh()->is_default)->toBe('yes');
    expect($previous->fresh()->is_default)->toBe('no');
});

test('the name stays unique within the company on update', function () {
    [$user, $company] = createUserWithCompany();

    Warehouse::factory()->create(['company_id' => $company->id, 'name' => 'Bodega ocupada']);
    $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('warehouses.update', ['company' => $company->id, 'id' => $warehouse->id]), [
            'name' => 'Bodega ocupada',
            'type' => 'main',
        ]);

    $response->assertSessionHasErrors('name');
});

test('a warehouse keeps its own name on update', function () {
    [$user, $company] = createUserWithCompany();

    $warehouse = Warehouse::factory()->create(['company_id' => $company->id, 'name' => 'Bodega estable']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('warehouses.update', ['company' => $company->id, 'id' => $warehouse->id]), [
            'name' => 'Bodega estable',
            'type' => 'branch',
        ]);

    $response->assertSessionHasNoErrors();
    expect($warehouse->fresh()->type)->toBe('branch');
});

test('a user without permission cannot update a warehouse', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['warehouses.list']);

    $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('warehouses.update', ['company' => $company->id, 'id' => $warehouse->id]), [
            'name' => 'Prohibida',
            'type' => 'main',
        ]);

    $response->assertForbidden();
});
