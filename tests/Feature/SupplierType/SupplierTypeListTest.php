<?php

declare(strict_types=1);

use App\Modules\SupplierType\Models\SupplierType;

use function Pest\Laravel\actingAs;

test('the supplier types index renders with supplier types', function () {
    [$user, $company] = createUserWithCompany();

    SupplierType::factory()->count(3)->create(['company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('supplier-types.index', ['company' => $company->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('supplier-types/index')
        ->has('supplierTypes', 3)
        ->where('meta.total', 3)
    );
});

test('supplier types can be filtered by name', function () {
    [$user, $company] = createUserWithCompany();

    SupplierType::factory()->create(['company_id' => $company->id, 'name' => 'Nacional']);
    SupplierType::factory()->create(['company_id' => $company->id, 'name' => 'Importador']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('supplier-types.index', ['company' => $company->id, 'name' => 'Nacio']));

    $response->assertInertia(fn ($page) => $page
        ->has('supplierTypes', 1)
        ->where('supplierTypes.0.name', 'Nacional')
    );
});

test('supplier types can be filtered by code', function () {
    [$user, $company] = createUserWithCompany();

    $target = SupplierType::factory()->create(['company_id' => $company->id, 'code' => 'TPR000042']);
    SupplierType::factory()->create(['company_id' => $company->id, 'code' => 'TPR000099']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('supplier-types.index', ['company' => $company->id, 'code' => 'TPR000042']));

    $response->assertInertia(fn ($page) => $page
        ->has('supplierTypes', 1)
        ->where('supplierTypes.0.id', $target->id)
    );
});

test('supplier types can be filtered by status', function () {
    [$user, $company] = createUserWithCompany();

    SupplierType::factory()->create(['company_id' => $company->id, 'status' => 'active']);
    SupplierType::factory()->inactive()->create(['company_id' => $company->id]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('supplier-types.index', ['company' => $company->id, 'status' => 'inactive']));

    $response->assertInertia(fn ($page) => $page->has('supplierTypes', 1));
});

test('supplier type filters combine with AND', function () {
    [$user, $company] = createUserWithCompany();

    $target = SupplierType::factory()->create([
        'company_id' => $company->id,
        'name' => 'Servicios generales',
        'code' => 'TPR000010',
    ]);
    SupplierType::factory()->create([
        'company_id' => $company->id,
        'name' => 'Servicios técnicos',
        'code' => 'TPR000011',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('supplier-types.index', [
            'company' => $company->id,
            'name' => 'Servicios',
            'code' => 'TPR000010',
        ]));

    $response->assertInertia(fn ($page) => $page
        ->has('supplierTypes', 1)
        ->where('supplierTypes.0.id', $target->id)
    );
});

test('the index only shows supplier types from the active company', function () {
    [$user, $company] = createUserWithCompany();

    SupplierType::factory()->count(2)->create(['company_id' => $company->id]);
    SupplierType::factory()->count(3)->create();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('supplier-types.index', ['company' => $company->id]));

    $response->assertInertia(fn ($page) => $page
        ->has('supplierTypes', 2)
        ->where('meta.total', 2)
    );
});

test('a user without permission cannot list supplier types', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, []);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->get(route('supplier-types.index', ['company' => $company->id]));

    $response->assertForbidden();
});
