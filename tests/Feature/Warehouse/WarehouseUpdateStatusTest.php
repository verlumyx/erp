<?php

declare(strict_types=1);

use App\Modules\Warehouse\Models\Warehouse;

use function Pest\Laravel\actingAs;

test('a warehouse can be deactivated', function () {
    [$user, $company] = createUserWithCompany();

    $warehouse = Warehouse::factory()->create(['company_id' => $company->id, 'status' => 'active']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('warehouses.update-status', ['company' => $company->id, 'id' => $warehouse->id]), [
            'status' => 'inactive',
        ]);

    $response->assertRedirect(route('warehouses.index', ['company' => $company->id]));
    expect($warehouse->fresh()->status)->toBe('inactive');
});

test('a warehouse can be reactivated', function () {
    [$user, $company] = createUserWithCompany();

    $warehouse = Warehouse::factory()->inactive()->create(['company_id' => $company->id]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('warehouses.update-status', ['company' => $company->id, 'id' => $warehouse->id]), [
            'status' => 'active',
        ]);

    expect($warehouse->fresh()->status)->toBe('active');
});

test('the status must be a valid value', function () {
    [$user, $company] = createUserWithCompany();

    $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('warehouses.update-status', ['company' => $company->id, 'id' => $warehouse->id]), [
            'status' => 'deleted',
        ]);

    $response->assertSessionHasErrors('status');
});

test('a user without permission cannot change the warehouse status', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['warehouses.list']);

    $warehouse = Warehouse::factory()->create(['company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('warehouses.update-status', ['company' => $company->id, 'id' => $warehouse->id]), [
            'status' => 'inactive',
        ]);

    $response->assertForbidden();
});
