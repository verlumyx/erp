<?php

declare(strict_types=1);

use App\Modules\SupplierType\Models\SupplierType;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

test('a supplier type can be created', function () {
    [$user, $company] = createUserWithCompany();

    $id = (string) Str::uuid7();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('supplier-types.store', ['company' => $company->id]), [
            'id' => $id,
            'name' => 'Nacional',
            'description' => 'Proveedores del país',
        ]);

    $response->assertRedirect(route('supplier-types.index', ['company' => $company->id]));
    $response->assertSessionHasNoErrors();

    $supplierType = SupplierType::find($id);
    expect($supplierType)->not->toBeNull();
    expect($supplierType->name)->toBe('Nacional');
    expect($supplierType->status)->toBe('active');
    expect($supplierType->created_by)->toBe($user->id);
    expect($supplierType->company_id)->toBe($company->id);
    expect($supplierType->code)->toBe('TPR000001');
});

test('the code auto-increments per company', function () {
    [$user, $company] = createUserWithCompany();

    $first = (string) Str::uuid7();
    $second = (string) Str::uuid7();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('supplier-types.store', ['company' => $company->id]), [
            'id' => $first,
            'name' => 'Nacional',
        ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('supplier-types.store', ['company' => $company->id]), [
            'id' => $second,
            'name' => 'Importador',
        ]);

    expect(SupplierType::find($first)->code)->toBe('TPR000001');
    expect(SupplierType::find($second)->code)->toBe('TPR000002');
});

test('each company has its own code sequence', function () {
    [$userA, $companyA] = createUserWithCompany();
    [$userB, $companyB] = createUserWithCompany();

    $idA = (string) Str::uuid7();
    $idB = (string) Str::uuid7();

    actingAs($userA)
        ->withSession(['current_company_id' => $companyA->id])
        ->post(route('supplier-types.store', ['company' => $companyA->id]), [
            'id' => $idA,
            'name' => 'Servicios',
        ]);

    actingAs($userB)
        ->withSession(['current_company_id' => $companyB->id])
        ->post(route('supplier-types.store', ['company' => $companyB->id]), [
            'id' => $idB,
            'name' => 'Servicios',
        ]);

    expect(SupplierType::find($idA)->code)->toBe('TPR000001');
    expect(SupplierType::find($idB)->code)->toBe('TPR000001');
});

test('a supplier type can be created without a description', function () {
    [$user, $company] = createUserWithCompany();

    $id = (string) Str::uuid7();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('supplier-types.store', ['company' => $company->id]), [
            'id' => $id,
            'name' => 'Importador',
        ]);

    $response->assertSessionHasNoErrors();
    expect(SupplierType::find($id)?->description)->toBeNull();
});

test('the name is required', function () {
    [$user, $company] = createUserWithCompany();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('supplier-types.store', ['company' => $company->id]), [
            'id' => (string) Str::uuid7(),
            'name' => '',
        ]);

    $response->assertSessionHasErrors('name');
});

test('the name must be unique per company', function () {
    [$user, $company] = createUserWithCompany();

    SupplierType::factory()->create(['company_id' => $company->id, 'name' => 'Nacional']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('supplier-types.store', ['company' => $company->id]), [
            'id' => (string) Str::uuid7(),
            'name' => 'Nacional',
        ]);

    $response->assertSessionHasErrors('name');
});

test('another company can reuse the same name', function () {
    [$user, $company] = createUserWithCompany();

    SupplierType::factory()->create(['name' => 'Nacional']);

    $id = (string) Str::uuid7();

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('supplier-types.store', ['company' => $company->id]), [
            'id' => $id,
            'name' => 'Nacional',
        ]);

    $response->assertSessionHasNoErrors();
    expect(SupplierType::find($id))->not->toBeNull();
});

test('a user without permission cannot create a supplier type', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['supplier-types.list']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('supplier-types.store', ['company' => $company->id]), [
            'id' => (string) Str::uuid7(),
            'name' => 'Forbidden',
        ]);

    $response->assertForbidden();
});
