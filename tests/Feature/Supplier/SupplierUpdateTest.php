<?php

declare(strict_types=1);

use App\Modules\Supplier\Models\Supplier;
use App\Modules\Supplier\Models\SupplierAddress;
use App\Modules\Supplier\Models\SupplierContact;

use function Pest\Laravel\actingAs;

test('a supplier can be updated', function () {
    [$user, $company] = createUserWithCompany();

    $supplier = Supplier::factory()->create([
        'company_id' => $company->id,
        'name' => 'Nombre viejo',
        'payment_term_days' => 0,
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('suppliers.update', ['company' => $company->id, 'id' => $supplier->id]),
            supplierPayload([
                'name' => 'Nombre nuevo',
                'payment_term_days' => 45,
                'email' => $supplier->email,
            ])
        );

    $response->assertRedirect(
        route('suppliers.show', ['company' => $company->id, 'id' => $supplier->id])
    );
    $response->assertSessionHasNoErrors();

    $supplier->refresh();
    expect($supplier->name)->toBe('Nombre nuevo');
    expect($supplier->payment_term_days)->toBe(45);
});

test('updating never touches the derived balances', function () {
    [$user, $company] = createUserWithCompany();

    $supplier = Supplier::factory()->withBalance(250)->create(['company_id' => $company->id]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('suppliers.update', ['company' => $company->id, 'id' => $supplier->id]),
            supplierPayload(['current_balance' => 0, 'advance_balance' => 900])
        );

    $supplier->refresh();
    expect((float) $supplier->current_balance)->toBe(250.0);
    expect((float) $supplier->advance_balance)->toBe(0.0);
});

test('an existing contact is updated instead of duplicated', function () {
    [$user, $company] = createUserWithCompany();

    $supplier = Supplier::factory()->create(['company_id' => $company->id]);
    $contact = SupplierContact::factory()->create([
        'supplier_id' => $supplier->id,
        'company_id' => $company->id,
        'name' => 'Nombre viejo',
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('suppliers.update', ['company' => $company->id, 'id' => $supplier->id]),
            supplierPayload([
                'contacts' => [
                    ['id' => $contact->id, 'name' => 'Nombre nuevo', 'is_primary' => 'yes'],
                ],
            ])
        )
        ->assertSessionHasNoErrors();

    expect(SupplierContact::where('supplier_id', $supplier->id)->count())->toBe(1);
    expect($contact->fresh()->name)->toBe('Nombre nuevo');
    expect($contact->fresh()->is_primary)->toBe('yes');
});

test('a contact that is no longer sent is deactivated, not deleted', function () {
    [$user, $company] = createUserWithCompany();

    $supplier = Supplier::factory()->create(['company_id' => $company->id]);
    $contact = SupplierContact::factory()->create([
        'supplier_id' => $supplier->id,
        'company_id' => $company->id,
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('suppliers.update', ['company' => $company->id, 'id' => $supplier->id]),
            supplierPayload(['contacts' => []])
        )
        ->assertSessionHasNoErrors();

    expect(SupplierContact::find($contact->id))->not->toBeNull();
    expect($contact->fresh()->status)->toBe('inactive');
});

test('an address that is no longer sent is deactivated, not deleted', function () {
    [$user, $company] = createUserWithCompany();

    $supplier = Supplier::factory()->create(['company_id' => $company->id]);
    $address = SupplierAddress::factory()->create([
        'supplier_id' => $supplier->id,
        'company_id' => $company->id,
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('suppliers.update', ['company' => $company->id, 'id' => $supplier->id]),
            supplierPayload([
                'addresses' => [
                    ['type' => 'pickup', 'address' => 'Galpón 12', 'is_default' => 'no'],
                ],
            ])
        )
        ->assertSessionHasNoErrors();

    expect($address->fresh()->status)->toBe('inactive');
    expect(SupplierAddress::where('supplier_id', $supplier->id)->count())->toBe(2);
});

test('a contact id belonging to another supplier is added as a new row', function () {
    [$user, $company] = createUserWithCompany();

    $supplier = Supplier::factory()->create(['company_id' => $company->id]);
    $other = Supplier::factory()->create(['company_id' => $company->id]);
    $foreignContact = SupplierContact::factory()->create([
        'supplier_id' => $other->id,
        'company_id' => $company->id,
        'name' => 'Contacto ajeno',
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('suppliers.update', ['company' => $company->id, 'id' => $supplier->id]),
            supplierPayload([
                'contacts' => [
                    ['id' => $foreignContact->id, 'name' => 'Secuestrado', 'is_primary' => 'no'],
                ],
            ])
        )
        ->assertSessionHasNoErrors();

    expect($foreignContact->fresh()->name)->toBe('Contacto ajeno');
    expect($foreignContact->fresh()->supplier_id)->toBe($other->id);
    expect(SupplierContact::where('supplier_id', $supplier->id)->count())->toBe(1);
});

test('the rif of another supplier in the same company is rejected', function () {
    [$user, $company] = createUserWithCompany();

    Supplier::factory()->create([
        'company_id' => $company->id,
        'document_type' => 'J',
        'document_number' => '123456789',
    ]);
    $supplier = Supplier::factory()->create(['company_id' => $company->id]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('suppliers.update', ['company' => $company->id, 'id' => $supplier->id]),
            supplierPayload()
        )
        ->assertSessionHasErrors('document_number');
});

test('a supplier keeps its own rif when updated', function () {
    [$user, $company] = createUserWithCompany();

    $supplier = Supplier::factory()->create([
        'company_id' => $company->id,
        'document_type' => 'J',
        'document_number' => '123456789',
    ]);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('suppliers.update', ['company' => $company->id, 'id' => $supplier->id]),
            supplierPayload(['email' => $supplier->email])
        )
        ->assertSessionHasNoErrors();
});

test('a supplier of another company cannot be updated', function () {
    [$user, $company] = createUserWithCompany();

    $foreign = Supplier::factory()->create();

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('suppliers.update', ['company' => $company->id, 'id' => $foreign->id]),
            supplierPayload()
        )
        ->assertNotFound();
});

test('a user without permission cannot update a supplier', function () {
    [$user, $company] = createUserWithCompany();
    $supplier = Supplier::factory()->create(['company_id' => $company->id]);
    assignRoleWithPermissions($user, $company, ['suppliers.list']);

    actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('suppliers.update', ['company' => $company->id, 'id' => $supplier->id]),
            supplierPayload()
        )
        ->assertForbidden();
});
