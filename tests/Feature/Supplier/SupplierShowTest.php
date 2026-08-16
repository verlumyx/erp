<?php

declare(strict_types=1);

use App\Modules\Supplier\Models\Supplier;
use App\Modules\Supplier\Models\SupplierAddress;
use App\Modules\Supplier\Models\SupplierContact;

use function Pest\Laravel\actingAs;

test('the supplier detail is rendered with its contacts and addresses', function () {
    [$user, $company] = createUserWithCompany();

    $supplier = Supplier::factory()->create(['company_id' => $company->id]);
    SupplierContact::factory()->primary()->create([
        'supplier_id' => $supplier->id,
        'company_id' => $company->id,
        'name' => 'María Pérez',
    ]);
    SupplierAddress::factory()->default()->create([
        'supplier_id' => $supplier->id,
        'company_id' => $company->id,
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('suppliers.show', ['company' => $company->id, 'id' => $supplier->id]));

    $response->assertOk();
    $response->assertInertia(
        fn ($page) => $page
            ->component('suppliers/show')
            ->where('supplier.id', $supplier->id)
            ->has('supplier.contacts', 1)
            ->where('supplier.contacts.0.name', 'María Pérez')
            ->where('supplier.contacts.0.is_primary', 'yes')
            ->has('supplier.addresses', 1)
            ->where('supplier.addresses.0.is_default', 'yes')
    );
});

test('a supplier of another company is not found', function () {
    [$user, $company] = createUserWithCompany();

    $foreign = Supplier::factory()->create();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('suppliers.show', ['company' => $company->id, 'id' => $foreign->id]))
        ->assertNotFound();
});

test('the edit form is rendered with the supplier and its options', function () {
    [$user, $company] = createUserWithCompany();

    $supplier = Supplier::factory()->create(['company_id' => $company->id]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('suppliers.edit', ['company' => $company->id, 'id' => $supplier->id]))
        ->assertInertia(
            fn ($page) => $page
                ->component('suppliers/edit')
                ->where('supplier.id', $supplier->id)
                ->has('options.supplierTypes')
        );
});

test('a user without permission cannot see a supplier', function () {
    [$user, $company] = createUserWithCompany();
    $supplier = Supplier::factory()->create(['company_id' => $company->id]);
    assignRoleWithPermissions($user, $company, ['suppliers.list']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('suppliers.show', ['company' => $company->id, 'id' => $supplier->id]))
        ->assertForbidden();
});
