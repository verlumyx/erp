<?php

declare(strict_types=1);

use App\Modules\SupplierType\Models\SupplierType;

use function Pest\Laravel\actingAs;

test('a supplier type status can be changed', function () {
    [$user, $company] = createUserWithCompany();

    $supplierType = SupplierType::factory()->create(['company_id' => $company->id, 'status' => 'active']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('supplier-types.update-status', ['company' => $company->id, 'id' => $supplierType->id]), [
            'status' => 'inactive',
        ]);

    $response->assertRedirect(route('supplier-types.index', ['company' => $company->id]));
    $response->assertSessionHas('success');
    expect($supplierType->fresh()->status)->toBe('inactive');
});

test('the status must be a valid value', function () {
    [$user, $company] = createUserWithCompany();

    $supplierType = SupplierType::factory()->create(['company_id' => $company->id, 'status' => 'active']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('supplier-types.update-status', ['company' => $company->id, 'id' => $supplierType->id]), [
            'status' => 'deleted',
        ]);

    $response->assertSessionHasErrors('status');
    expect($supplierType->fresh()->status)->toBe('active');
});

test('a user without permission cannot change the supplier type status', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['supplier-types.list']);

    $supplierType = SupplierType::factory()->create(['company_id' => $company->id, 'status' => 'active']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('supplier-types.update-status', ['company' => $company->id, 'id' => $supplierType->id]), [
            'status' => 'inactive',
        ]);

    $response->assertForbidden();
});
