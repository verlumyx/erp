<?php

declare(strict_types=1);

use App\Modules\Supplier\Models\Supplier;
use App\Modules\SupplierAdvance\Models\SupplierAdvance;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

test('a supplier advance can be created', function () {
    [$user, $company, $supplier] = supplierAdvanceScenario();

    $payload = supplierAdvancePayload($supplier, [
        'reference' => '0102-4471',
        'bank_account' => 'Banco Nacional 0102',
        'notes' => 'Adelanto del 40% acordado con el proveedor.',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('supplier-advances.store', ['company' => $company->id]), $payload);

    $response->assertRedirect(route('supplier-advances.index', ['company' => $company->id]));
    $response->assertSessionHasNoErrors();

    $advance = SupplierAdvance::find($payload['id']);
    expect($advance)->not->toBeNull();
    expect($advance->code)->toBe('ANP000001');
    expect($advance->status)->toBe('draft');
    expect($advance->company_id)->toBe($company->id);
    expect($advance->created_by)->toBe($user->id);
    expect($advance->supplier_id)->toBe($supplier->id);
    expect($advance->reference)->toBe('0102-4471');
    expect((float) $advance->amount)->toBe(400.0);
    expect((float) $advance->applied_amount)->toBe(0.0);
    /** Nace entero disponible: nada se ha aplicado todavía. */
    expect((float) $advance->balance)->toBe(400.0);
});

/** Capturarlo no compromete nada: el crédito lo da confirmar su pago. */
test('a draft advance does not touch the credit of the supplier', function () {
    [$user, $company, $supplier] = supplierAdvanceScenario();

    createSupplierAdvance($user, $company, $supplier);

    expect((float) $supplier->refresh()->advance_balance)->toBe(0.0);
});

test('the advance can stem from a purchase order of the same supplier', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = supplierAdvanceScenario();

    $order = sourcePurchaseOrder($user, $company, $supplier, $warehouse, $item, $unit);

    $advance = createSupplierAdvance($user, $company, $supplier, [
        'purchase_order_id' => $order->id,
    ]);

    expect($advance->purchase_order_id)->toBe($order->id);
});

test('the order of another supplier is rejected', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = supplierAdvanceScenario();

    $other = Supplier::factory()->create(['company_id' => $company->id]);
    $order = sourcePurchaseOrder($user, $company, $other, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('supplier-advances.store', ['company' => $company->id]),
            supplierAdvancePayload($supplier, ['purchase_order_id' => $order->id]),
        )
        ->assertSessionHasErrors('purchase_order_id');

    expect(SupplierAdvance::count())->toBe(0);
});

test('the amount of the advance is required and positive', function () {
    [$user, $company, $supplier] = supplierAdvanceScenario();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('supplier-advances.store', ['company' => $company->id]),
            supplierAdvancePayload($supplier, ['amount' => 0]),
        )
        ->assertSessionHasErrors('amount');
});

test('the supplier of another company is not available', function () {
    [$user, $company] = supplierAdvanceScenario();

    $stranger = Supplier::factory()->create();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('supplier-advances.store', ['company' => $company->id]),
            supplierAdvancePayload($stranger),
        )
        ->assertSessionHasErrors('supplier_id');
});

test('the code is sequential per company', function () {
    [$user, $company, $supplier] = supplierAdvanceScenario();

    $first = createSupplierAdvance($user, $company, $supplier);
    $second = createSupplierAdvance($user, $company, $supplier);

    expect($first->code)->toBe('ANP000001');
    expect($second->code)->toBe('ANP000002');
});

test('the create form is rendered', function () {
    [$user, $company] = supplierAdvanceScenario();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('supplier-advances.create', ['company' => $company->id]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('supplier-advances/create'));
});

test('a user without permission cannot create an advance', function () {
    [$user, $company, $supplier] = supplierAdvanceScenario();

    assignRoleWithPermissions($user, $company, ['supplier-advances.list']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('supplier-advances.store', ['company' => $company->id]),
            supplierAdvancePayload($supplier),
        )
        ->assertForbidden();
});
