<?php

declare(strict_types=1);

use App\Modules\PurchaseInvoice\Models\PurchaseInvoice;

use function Pest\Laravel\actingAs;

/** Una factura de la empresa activa en el estado que pida el test. */
function purchaseInvoiceInStatus(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    \App\Modules\Supplier\Models\Supplier $supplier,
    \App\Modules\Warehouse\Models\Warehouse $warehouse,
    string $status,
    array $overrides = [],
): PurchaseInvoice {
    return PurchaseInvoice::factory()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'created_by' => $user->id,
        'status' => $status,
        ...$overrides,
    ]);
}

test('a draft invoice can be confirmed', function () {
    [$user, $company, $supplier, $warehouse] = purchaseInvoiceScenario();

    $invoice = purchaseInvoiceInStatus($user, $company, $supplier, $warehouse, 'draft');

    $response = actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-invoices.update-status', ['company' => $company->id, 'id' => $invoice->id]), [
            'status' => 'confirmed',
        ]);

    $response->assertRedirect(route('purchase-invoices.show', ['company' => $company->id, 'id' => $invoice->id]));
    $response->assertSessionHasNoErrors();

    expect($invoice->refresh()->status)->toBe('confirmed');
});

test('confirming does not recalculate the frozen rate', function () {
    [$user, $company, $supplier, $warehouse] = purchaseInvoiceScenario();

    $invoice = purchaseInvoiceInStatus($user, $company, $supplier, $warehouse, 'draft', [
        'exchange_rate' => 36.5,
        'total' => 100,
        'total_ves' => 3650,
    ]);

    /** La tasa del día cambia después de emitir: la factura no la estrena. */
    \App\Modules\ExchangeRate\Models\ExchangeRate::query()
        ->where('company_id', $company->id)
        ->where('currency', 'USD')
        ->update(['rate' => 99.0]);

    app()->forgetScopedInstances();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-invoices.update-status', ['company' => $company->id, 'id' => $invoice->id]), [
            'status' => 'confirmed',
        ])
        ->assertSessionHasNoErrors();

    $invoice->refresh();
    expect((float) $invoice->exchange_rate)->toBe(36.5);
    expect((float) $invoice->total_ves)->toBe(3650.0);
});

test('cancelling requires a reason and stamps the cancellation', function () {
    [$user, $company, $supplier, $warehouse] = purchaseInvoiceScenario();

    $invoice = purchaseInvoiceInStatus($user, $company, $supplier, $warehouse, 'draft');

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-invoices.update-status', ['company' => $company->id, 'id' => $invoice->id]), [
            'status' => 'cancelled',
        ])
        ->assertSessionHasErrors('cancellation_reason');

    expect($invoice->refresh()->status)->toBe('draft');

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-invoices.update-status', ['company' => $company->id, 'id' => $invoice->id]), [
            'status' => 'cancelled',
            'cancellation_reason' => 'El proveedor emitió la factura con el RIF equivocado.',
        ])
        ->assertSessionHasNoErrors();

    $invoice->refresh();
    expect($invoice->status)->toBe('cancelled');
    expect($invoice->cancelled_at)->not->toBeNull();
    expect($invoice->cancellation_reason)->toBe('El proveedor emitió la factura con el RIF equivocado.');
});

test('an invoice with payments applied cannot be cancelled', function () {
    [$user, $company, $supplier, $warehouse] = purchaseInvoiceScenario();

    $invoice = purchaseInvoiceInStatus($user, $company, $supplier, $warehouse, 'confirmed', [
        'total' => 100,
        'paid_amount' => 40,
        'balance' => 60,
        'payment_status' => 'partial',
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-invoices.update-status', ['company' => $company->id, 'id' => $invoice->id]), [
            'status' => 'cancelled',
            'cancellation_reason' => 'Se registró dos veces.',
        ])
        ->assertSessionHasErrors('status');

    expect($invoice->refresh()->status)->toBe('confirmed');
});

test('a forbidden transition is rejected', function () {
    [$user, $company, $supplier, $warehouse] = purchaseInvoiceScenario();

    $invoice = purchaseInvoiceInStatus($user, $company, $supplier, $warehouse, 'draft');

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-invoices.update-status', ['company' => $company->id, 'id' => $invoice->id]), [
            'status' => 'completed',
        ])
        ->assertSessionHasErrors('status');

    expect($invoice->refresh()->status)->toBe('draft');
});

test('a cancelled invoice is a dead end', function () {
    [$user, $company, $supplier, $warehouse] = purchaseInvoiceScenario();

    $invoice = purchaseInvoiceInStatus($user, $company, $supplier, $warehouse, 'cancelled');

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-invoices.update-status', ['company' => $company->id, 'id' => $invoice->id]), [
            'status' => 'confirmed',
        ])
        ->assertSessionHasErrors('status');

    expect($invoice->refresh()->status)->toBe('cancelled');
});

test('a user without permission cannot change the status', function () {
    [$user, $company, $supplier, $warehouse] = purchaseInvoiceScenario();

    $invoice = purchaseInvoiceInStatus($user, $company, $supplier, $warehouse, 'draft');

    assignRoleWithPermissions($user, $company, ['purchase-invoices.list']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-invoices.update-status', ['company' => $company->id, 'id' => $invoice->id]), [
            'status' => 'confirmed',
        ])
        ->assertForbidden();
});
