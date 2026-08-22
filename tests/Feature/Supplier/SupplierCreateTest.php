<?php

declare(strict_types=1);

use App\Modules\Supplier\Models\Supplier;
use App\Modules\SupplierType\Models\SupplierType;

use function Pest\Laravel\actingAs;

test('a supplier can be created', function () {
    [$user, $company] = createUserWithCompany();

    $payload = supplierPayload([
        'legal_name' => 'Distribuidora Andina, Compañía Anónima',
        'email' => 'compras@andina.com',
        'payment_term_days' => 30,
        'credit_limit' => 5000,
        'lead_time_days' => 7,
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('suppliers.store', ['company' => $company->id]), $payload);

    $response->assertRedirect(route('suppliers.index', ['company' => $company->id]));
    $response->assertSessionHasNoErrors();

    $supplier = Supplier::find($payload['id']);
    expect($supplier)->not->toBeNull();
    expect($supplier->name)->toBe('Distribuidora Andina C.A.');
    expect($supplier->document_type)->toBe('J');
    expect($supplier->document_number)->toBe('123456789');
    expect($supplier->payment_term_days)->toBe(30);
    expect((float) $supplier->credit_limit)->toBe(5000.0);
    expect($supplier->lead_time_days)->toBe(7);
    expect($supplier->code)->toBe('PRO000001');
    expect($supplier->status)->toBe('active');
    expect($supplier->company_id)->toBe($company->id);
    expect($supplier->created_by)->toBe($user->id);
});

/**
 * La moneda viaja del formulario: el backend no la sustituye por un `USD`
 * fijo cuando la empresa lleva sus cifras en otra.
 */
test('the supplier keeps the currency chosen in the form', function () {
    [$user, $company] = createUserWithCompany();

    $payload = supplierPayload(['currency' => 'EUR']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('suppliers.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    expect(Supplier::find($payload['id'])->currency)->toBe('EUR');
});

test('the balances always start at zero and cannot be sent from the client', function () {
    [$user, $company] = createUserWithCompany();

    $payload = supplierPayload([
        'current_balance' => 999,
        'advance_balance' => 500,
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('suppliers.store', ['company' => $company->id]), $payload);

    $supplier = Supplier::find($payload['id']);
    expect((float) $supplier->current_balance)->toBe(0.0);
    expect((float) $supplier->advance_balance)->toBe(0.0);
});

test('the code auto-increments per company', function () {
    [$user, $company] = createUserWithCompany();

    $first = supplierPayload(['document_number' => '111111111']);
    $second = supplierPayload(['document_number' => '222222222', 'name' => 'Segundo']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('suppliers.store', ['company' => $company->id]), $first);
    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('suppliers.store', ['company' => $company->id]), $second);

    expect(Supplier::find($first['id'])->code)->toBe('PRO000001');
    expect(Supplier::find($second['id'])->code)->toBe('PRO000002');
});

test('a supplier can be created with its contacts and addresses', function () {
    [$user, $company] = createUserWithCompany();

    $supplierType = SupplierType::factory()->create(['company_id' => $company->id]);

    $payload = supplierPayload([
        'supplier_type_id' => $supplierType->id,
        'contacts' => [
            [
                'name' => 'María Pérez',
                'position' => 'Ventas',
                'email' => 'maria@andina.com',
                'phone' => '+58 412 000 0000',
                'is_primary' => 'yes',
            ],
            ['name' => 'Luis Rojas', 'is_primary' => 'no'],
        ],
        'addresses' => [
            [
                'type' => 'billing',
                'address' => 'Av. Principal, Torre A',
                'city' => 'Caracas',
                'is_default' => 'yes',
            ],
            [
                'type' => 'pickup',
                'address' => 'Zona Industrial, Galpón 12',
                'is_default' => 'yes',
            ],
        ],
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('suppliers.store', ['company' => $company->id]), $payload);

    $response->assertSessionHasNoErrors();

    $supplier = Supplier::with(['contacts', 'addresses'])->find($payload['id']);
    expect($supplier->supplier_type_id)->toBe($supplierType->id);
    expect($supplier->contacts)->toHaveCount(2);
    expect($supplier->addresses)->toHaveCount(2);

    $primary = $supplier->contacts->firstWhere('is_primary', 'yes');
    expect($primary->name)->toBe('María Pérez');
    expect($primary->company_id)->toBe($company->id);

    $billing = $supplier->addresses->firstWhere('type', 'billing');
    expect($billing->city)->toBe('Caracas');
    expect($billing->is_default)->toBe('yes');
    expect($billing->company_id)->toBe($company->id);
});

test('the name is required', function () {
    [$user, $company] = createUserWithCompany();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('suppliers.store', ['company' => $company->id]), supplierPayload(['name' => '']))
        ->assertSessionHasErrors('name');
});

test('the document number is required and must be digits only', function () {
    [$user, $company] = createUserWithCompany();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('suppliers.store', ['company' => $company->id]), supplierPayload(['document_number' => '']))
        ->assertSessionHasErrors('document_number');

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('suppliers.store', ['company' => $company->id]), supplierPayload(['document_number' => 'J-1234-5']))
        ->assertSessionHasErrors('document_number');
});

test('the rif must be unique within the company', function () {
    [$user, $company] = createUserWithCompany();

    Supplier::factory()->create([
        'company_id' => $company->id,
        'document_type' => 'J',
        'document_number' => '123456789',
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('suppliers.store', ['company' => $company->id]), supplierPayload())
        ->assertSessionHasErrors('document_number');
});

test('the same number with a different rif letter is accepted', function () {
    [$user, $company] = createUserWithCompany();

    Supplier::factory()->create([
        'company_id' => $company->id,
        'document_type' => 'J',
        'document_number' => '123456789',
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('suppliers.store', ['company' => $company->id]), supplierPayload([
            'document_type' => 'V',
        ]))
        ->assertSessionHasNoErrors();
});

test('another company can reuse the same rif', function () {
    [$user, $company] = createUserWithCompany();

    Supplier::factory()->create([
        'document_type' => 'J',
        'document_number' => '123456789',
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('suppliers.store', ['company' => $company->id]), supplierPayload())
        ->assertSessionHasNoErrors();
});

test('the email must be unique within the company', function () {
    [$user, $company] = createUserWithCompany();

    Supplier::factory()->create([
        'company_id' => $company->id,
        'email' => 'compras@andina.com',
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('suppliers.store', ['company' => $company->id]), supplierPayload([
            'email' => 'compras@andina.com',
        ]))
        ->assertSessionHasErrors('email');
});

test('only one contact can be the primary one', function () {
    [$user, $company] = createUserWithCompany();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('suppliers.store', ['company' => $company->id]), supplierPayload([
            'contacts' => [
                ['name' => 'María Pérez', 'is_primary' => 'yes'],
                ['name' => 'Luis Rojas', 'is_primary' => 'yes'],
            ],
        ]))
        ->assertSessionHasErrors('contacts');
});

test('only one address per type can be the default one', function () {
    [$user, $company] = createUserWithCompany();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('suppliers.store', ['company' => $company->id]), supplierPayload([
            'addresses' => [
                ['type' => 'billing', 'address' => 'Av. Principal', 'is_default' => 'yes'],
                ['type' => 'billing', 'address' => 'Av. Secundaria', 'is_default' => 'yes'],
            ],
        ]))
        ->assertSessionHasErrors('addresses.1.is_default');
});

test('a supplier type from another company is rejected', function () {
    [$user, $company] = createUserWithCompany();

    $foreignType = SupplierType::factory()->create();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('suppliers.store', ['company' => $company->id]), supplierPayload([
            'supplier_type_id' => $foreignType->id,
        ]))
        ->assertSessionHasErrors('supplier_type_id');
});

test('a user without permission cannot create a supplier', function () {
    [$user, $company] = createUserWithCompany();
    assignRoleWithPermissions($user, $company, ['suppliers.list']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('suppliers.store', ['company' => $company->id]), supplierPayload())
        ->assertForbidden();
});
