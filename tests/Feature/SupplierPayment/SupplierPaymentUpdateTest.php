<?php

declare(strict_types=1);

use App\Modules\SupplierPayment\Models\SupplierPayment;
use App\Modules\SupplierPayment\Models\SupplierPaymentApplication;

use function Pest\Laravel\actingAs;

test('a draft payment can be updated', function () {
    [$user, $company, $supplier] = supplierPaymentScenario();

    $payment = createSupplierPayment($user, $company, $supplier);

    $response = actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(
            route('supplier-payments.update', ['company' => $company->id, 'id' => $payment->id]),
            supplierPaymentPayload($supplier, [
                'amount' => 400,
                'payment_method' => 'check',
                'reference' => '0001234',
                'notes' => 'Se pagó con cheque, no por transferencia.',
            ]),
        );

    $response->assertRedirect(route('supplier-payments.show', ['company' => $company->id, 'id' => $payment->id]));
    $response->assertSessionHasNoErrors();

    $payment->refresh();
    expect((float) $payment->amount)->toBe(400.0);
    expect($payment->payment_method)->toBe('check');
    expect($payment->reference)->toBe('0001234');
    expect($payment->code)->toBe('PGP000001');
});

test('dropping an invoice from the distribution reverses its row instead of deleting it', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = supplierPaymentScenario();

    $first = payablePurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);
    $second = payablePurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);

    $payment = createSupplierPayment($user, $company, $supplier, [
        'amount' => 300,
        'applications' => [
            ['purchase_invoice_id' => $first->id, 'applied_amount' => 250],
            ['purchase_invoice_id' => $second->id, 'applied_amount' => 50],
        ],
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(
            route('supplier-payments.update', ['company' => $company->id, 'id' => $payment->id]),
            supplierPaymentPayload($supplier, [
                'amount' => 300,
                'applications' => [
                    ['purchase_invoice_id' => $first->id, 'applied_amount' => 200],
                ],
            ]),
        )
        ->assertSessionHasNoErrors();

    expect(SupplierPaymentApplication::count())->toBe(2);

    $kept = SupplierPaymentApplication::where('purchase_invoice_id', $first->id)->first();
    expect($kept->status)->toBe('active');
    expect((float) $kept->applied_amount)->toBe(200.0);

    $dropped = SupplierPaymentApplication::where('purchase_invoice_id', $second->id)->first();
    expect($dropped->status)->toBe('reversed');

    expect((float) $payment->refresh()->applied_amount)->toBe(200.0);
    expect((float) $payment->unapplied_amount)->toBe(100.0);
});

test('a confirmed payment can no longer be edited', function () {
    [$user, $company, $supplier] = supplierPaymentScenario();

    $payment = createSupplierPayment($user, $company, $supplier);

    moveSupplierPaymentTo($user, $company, $payment, 'confirmed')->assertSessionHasNoErrors();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(
            route('supplier-payments.update', ['company' => $company->id, 'id' => $payment->id]),
            supplierPaymentPayload($supplier, ['amount' => 999]),
        )
        ->assertSessionHasErrors('status');

    expect((float) $payment->refresh()->amount)->toBe(250.0);
});

test('the mirror payment of an advance is not editable', function () {
    [$user, $company, $supplier] = supplierPaymentScenario();

    $payment = SupplierPayment::factory()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'created_by' => $user->id,
        'origin_type' => 'advance',
        'origin_id' => (string) Illuminate\Support\Str::uuid7(),
        'status' => 'draft',
    ]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(
            route('supplier-payments.update', ['company' => $company->id, 'id' => $payment->id]),
            supplierPaymentPayload($supplier, ['amount' => 999]),
        )
        ->assertSessionHasErrors('origin_type');
});

test('the origin cannot be changed on update', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = supplierPaymentScenario();

    $invoice = payablePurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);

    $payment = createSupplierPayment($user, $company, $supplier);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(
            route('supplier-payments.update', ['company' => $company->id, 'id' => $payment->id]),
            supplierPaymentPayload($supplier, [
                'origin_type' => 'invoice',
                'origin_id' => $invoice->id,
            ]),
        )
        ->assertSessionHasNoErrors();

    $payment->refresh();
    expect($payment->origin_type)->toBe('supplier');
    expect($payment->origin_id)->toBeNull();
});

test('a user without permission cannot update a payment', function () {
    [$user, $company, $supplier] = supplierPaymentScenario();

    $payment = createSupplierPayment($user, $company, $supplier);

    assignRoleWithPermissions($user, $company, ['supplier-payments.list']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(
            route('supplier-payments.update', ['company' => $company->id, 'id' => $payment->id]),
            supplierPaymentPayload($supplier),
        )
        ->assertForbidden();
});
