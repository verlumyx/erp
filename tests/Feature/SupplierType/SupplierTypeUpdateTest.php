<?php

declare(strict_types=1);

use App\Modules\SupplierType\Models\SupplierType;

use function Pest\Laravel\actingAs;

test('a supplier type can be updated', function () {
    [$user, $company] = createUserWithCompany();

    $supplierType = SupplierType::factory()->create([
        'company_id' => $company->id,
        'name' => 'Nacional',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('supplier-types.update', ['company' => $company->id, 'id' => $supplierType->id]), [
            'name' => 'Nacional directo',
            'description' => 'Proveedores del país',
        ]);

    $response->assertRedirect(route('supplier-types.show', ['company' => $company->id, 'id' => $supplierType->id]));
    $response->assertSessionHasNoErrors();

    $supplierType->refresh();
    expect($supplierType->name)->toBe('Nacional directo');
    expect($supplierType->description)->toBe('Proveedores del país');
});

test('updating keeps its own name valid', function () {
    [$user, $company] = createUserWithCompany();

    $supplierType = SupplierType::factory()->create([
        'company_id' => $company->id,
        'name' => 'Importador',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('supplier-types.update', ['company' => $company->id, 'id' => $supplierType->id]), [
            'name' => 'Importador',
            'description' => 'Actualizado',
        ]);

    $response->assertSessionHasNoErrors();
    expect($supplierType->fresh()->description)->toBe('Actualizado');
});

test('the updated name must not collide with another supplier type', function () {
    [$user, $company] = createUserWithCompany();

    SupplierType::factory()->create(['company_id' => $company->id, 'name' => 'Nacional']);
    $supplierType = SupplierType::factory()->create(['company_id' => $company->id, 'name' => 'Importador']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('supplier-types.update', ['company' => $company->id, 'id' => $supplierType->id]), [
            'name' => 'Nacional',
        ]);

    $response->assertSessionHasErrors('name');
    expect($supplierType->fresh()->name)->toBe('Importador');
});

test('a user without permission cannot update a supplier type', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['supplier-types.list']);

    $supplierType = SupplierType::factory()->create(['company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(route('supplier-types.update', ['company' => $company->id, 'id' => $supplierType->id]), [
            'name' => 'Forbidden',
        ]);

    $response->assertForbidden();
});
