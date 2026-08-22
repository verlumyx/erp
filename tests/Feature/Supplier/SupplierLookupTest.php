<?php

declare(strict_types=1);

use App\Modules\Supplier\Models\Supplier;

use function Pest\Laravel\actingAs;

test('the lookup endpoint returns suppliers as select options', function () {
    [$user, $company] = createUserWithCompany();

    $supplier = Supplier::factory()->create([
        'company_id' => $company->id,
        'code' => 'PRO000001',
        'name' => 'Ferretería Central',
        'currency' => 'USD',
        'payment_term_days' => 30,
        'status' => 'active',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->getJson(route('suppliers.lookup', ['company' => $company->id]));

    $response->assertOk();
    $response->assertJsonPath('data.0.value', $supplier->id);
    $response->assertJsonPath('data.0.label', 'PRO000001 · Ferretería Central');
    $response->assertJsonPath('data.0.meta.currency', 'USD');
    $response->assertJsonPath('data.0.meta.payment_term_days', 30);
    $response->assertJsonPath('has_more', false);
});

test('the lookup endpoint searches by name, legal name, code and document', function (string $term) {
    [$user, $company] = createUserWithCompany();

    Supplier::factory()->create([
        'company_id' => $company->id,
        'code' => 'PRO000009',
        'name' => 'Distribuidora Andina',
        'legal_name' => 'Andina Suministros C.A.',
        'document_number' => '123456789',
        'status' => 'active',
    ]);
    Supplier::factory()->create([
        'company_id' => $company->id,
        'code' => 'PRO000010',
        'name' => 'Otro Proveedor',
        'legal_name' => 'Otro Proveedor C.A.',
        'document_number' => '987654321',
        'status' => 'active',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->getJson(route('suppliers.lookup', ['company' => $company->id, 'q' => $term]));

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
    $response->assertJsonPath('data.0.label', 'PRO000009 · Distribuidora Andina');
})->with(['Andina', 'Suministros', 'PRO000009', '123456789']);

test('the lookup endpoint only returns active suppliers of the active company', function () {
    [$user, $company] = createUserWithCompany();

    Supplier::factory()->create(['company_id' => $company->id, 'status' => 'active']);
    Supplier::factory()->create(['company_id' => $company->id, 'status' => 'inactive']);
    Supplier::factory()->create(['status' => 'active']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->getJson(route('suppliers.lookup', ['company' => $company->id]));

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
});

test('the lookup endpoint paginates and reports whether more pages remain', function () {
    [$user, $company] = createUserWithCompany();

    Supplier::factory()->count(5)->create(['company_id' => $company->id, 'status' => 'active']);

    $first = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->getJson(route('suppliers.lookup', [
            'company' => $company->id,
            'per_page' => 2,
            'page' => 1,
        ]));

    $first->assertOk();
    $first->assertJsonCount(2, 'data');
    $first->assertJsonPath('has_more', true);

    $last = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->getJson(route('suppliers.lookup', [
            'company' => $company->id,
            'per_page' => 2,
            'page' => 3,
        ]));

    $last->assertOk();
    $last->assertJsonCount(1, 'data');
    $last->assertJsonPath('has_more', false);
});

test('the lookup endpoint caps how many options a single page can ask for', function () {
    [$user, $company] = createUserWithCompany();

    Supplier::factory()->count(55)->create(['company_id' => $company->id, 'status' => 'active']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->getJson(route('suppliers.lookup', ['company' => $company->id, 'per_page' => 500]));

    $response->assertOk();
    $response->assertJsonCount(50, 'data');
});

test('hydrating by id also resolves a supplier deactivated after being chosen', function () {
    [$user, $company] = createUserWithCompany();

    $chosen = Supplier::factory()->create(['company_id' => $company->id, 'status' => 'inactive']);
    Supplier::factory()->count(3)->create(['company_id' => $company->id, 'status' => 'active']);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->getJson(route('suppliers.lookup', ['company' => $company->id, 'ids' => $chosen->id]));

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
    $response->assertJsonPath('data.0.value', $chosen->id);
});

test('a user without permission cannot look suppliers up', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['purchase-orders.list']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->getJson(route('suppliers.lookup', ['company' => $company->id]))
        ->assertForbidden();
});
