<?php

declare(strict_types=1);

use App\Modules\Supplier\Models\Supplier;

use function Pest\Laravel\actingAs;

test('a draft advance can be edited', function () {
    [$user, $company, $supplier] = supplierAdvanceScenario();

    $advance = createSupplierAdvance($user, $company, $supplier);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('supplier-advances.update', ['company' => $company->id, 'id' => $advance->id]),
            supplierAdvancePayload($supplier, [
                'amount' => 650,
                'payment_method' => 'cash',
                'reference' => 'Recibo 44',
                'notes' => 'Se aumentó el adelanto acordado.',
            ]),
        );

    $response->assertRedirect(route('supplier-advances.show', ['company' => $company->id, 'id' => $advance->id]));
    $response->assertSessionHasNoErrors();

    $advance->refresh();
    expect((float) $advance->amount)->toBe(650.0);
    expect((float) $advance->balance)->toBe(650.0);
    expect($advance->payment_method)->toBe('cash');
    expect($advance->reference)->toBe('Recibo 44');
    /** Editar no le cambia el código ni lo saca de borrador. */
    expect($advance->code)->toBe('ANP000001');
    expect($advance->status)->toBe('draft');
});

test('an approved advance is no longer editable', function () {
    [$user, $company, $supplier] = supplierAdvanceScenario();

    $advance = createSupplierAdvance($user, $company, $supplier);
    moveSupplierAdvanceTo($user, $company, $advance, 'pending_confirmation')->assertSessionHasNoErrors();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(
            route('supplier-advances.update', ['company' => $company->id, 'id' => $advance->id]),
            supplierAdvancePayload($supplier, ['amount' => 999]),
        )
        ->assertSessionHasErrors('status');

    expect((float) $advance->refresh()->amount)->toBe(400.0);
});

test('the edit form is rendered for a draft advance', function () {
    [$user, $company, $supplier] = supplierAdvanceScenario();

    $advance = createSupplierAdvance($user, $company, $supplier);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('supplier-advances.edit', ['company' => $company->id, 'id' => $advance->id]))
        ->assertOk()
        ->assertInertia(fn (\Inertia\Testing\AssertableInertia $page) => $page
            ->component('supplier-advances/edit')
            ->where('supplierAdvance.code', 'ANP000001'));
});

test('the advance cannot be moved to a supplier of another company', function () {
    [$user, $company, $supplier] = supplierAdvanceScenario();

    $advance = createSupplierAdvance($user, $company, $supplier);
    $stranger = Supplier::factory()->create();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(
            route('supplier-advances.update', ['company' => $company->id, 'id' => $advance->id]),
            supplierAdvancePayload($stranger),
        )
        ->assertSessionHasErrors('supplier_id');

    expect($advance->refresh()->supplier_id)->toBe($supplier->id);
});

test('a user without permission cannot edit an advance', function () {
    [$user, $company, $supplier] = supplierAdvanceScenario();

    $advance = createSupplierAdvance($user, $company, $supplier);

    assignRoleWithPermissions($user, $company, ['supplier-advances.list']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(
            route('supplier-advances.update', ['company' => $company->id, 'id' => $advance->id]),
            supplierAdvancePayload($supplier),
        )
        ->assertForbidden();
});
