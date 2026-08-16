<?php

declare(strict_types=1);

use App\Modules\Supplier\Models\Supplier;
use App\Modules\SupplierType\Models\SupplierType;

use function Pest\Laravel\actingAs;

test('the supplier list is rendered with its suppliers', function () {
    [$user, $company] = createUserWithCompany();

    Supplier::factory()->count(3)->create(['company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('suppliers.index', ['company' => $company->id]));

    $response->assertOk();
    $response->assertInertia(
        fn ($page) => $page
            ->component('suppliers/index')
            ->has('suppliers', 3)
            ->where('meta.total', 3)
            ->has('options.supplierTypes')
    );
});

test('the list only shows suppliers of the active company', function () {
    [$user, $company] = createUserWithCompany();

    Supplier::factory()->create(['company_id' => $company->id]);
    Supplier::factory()->count(2)->create();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('suppliers.index', ['company' => $company->id]))
        ->assertInertia(fn ($page) => $page->has('suppliers', 1));
});

test('suppliers can be filtered by name and by legal name', function () {
    [$user, $company] = createUserWithCompany();

    Supplier::factory()->create(['company_id' => $company->id, 'name' => 'Distribuidora Andina']);
    Supplier::factory()->create([
        'company_id' => $company->id,
        'name' => 'Otro proveedor',
        'legal_name' => 'Andina Holdings',
    ]);
    Supplier::factory()->create(['company_id' => $company->id, 'name' => 'Ferretería Central']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('suppliers.index', ['company' => $company->id, 'name' => 'Andina']))
        ->assertInertia(fn ($page) => $page->has('suppliers', 2));
});

test('suppliers can be filtered by document number', function () {
    [$user, $company] = createUserWithCompany();

    Supplier::factory()->create(['company_id' => $company->id, 'document_number' => '987654321']);
    Supplier::factory()->create(['company_id' => $company->id, 'document_number' => '111111111']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('suppliers.index', ['company' => $company->id, 'document_number' => '98765']))
        ->assertInertia(
            fn ($page) => $page->has('suppliers', 1)->where('suppliers.0.document_number', '987654321')
        );
});

test('suppliers can be filtered by supplier type and status', function () {
    [$user, $company] = createUserWithCompany();

    $supplierType = SupplierType::factory()->create(['company_id' => $company->id]);

    Supplier::factory()->create([
        'company_id' => $company->id,
        'supplier_type_id' => $supplierType->id,
    ]);
    Supplier::factory()->create(['company_id' => $company->id]);
    Supplier::factory()->inactive()->create(['company_id' => $company->id]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('suppliers.index', [
            'company' => $company->id,
            'supplier_type_id' => $supplierType->id,
        ]))
        ->assertInertia(fn ($page) => $page->has('suppliers', 1));

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('suppliers.index', ['company' => $company->id, 'status' => 'inactive']))
        ->assertInertia(fn ($page) => $page->has('suppliers', 1));
});

test('a user without permission cannot list suppliers', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['clients.list']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('suppliers.index', ['company' => $company->id]))
        ->assertForbidden();
});
