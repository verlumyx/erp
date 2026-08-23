<?php

declare(strict_types=1);

use App\Modules\SupplierAdvance\Models\SupplierAdvance;
use App\Modules\SupplierPayment\Models\SupplierPayment;

/** El pago espejo del anticipo, tal como lo busca la pantalla. */
function mirrorPaymentOf(SupplierAdvance $advance): ?SupplierPayment
{
    return SupplierPayment::query()
        ->where('origin_type', 'advance')
        ->where('origin_id', $advance->id)
        ->orderByDesc('created_at')
        ->first();
}

test('approving the advance creates its mirror payment in draft', function () {
    [$user, $company, $supplier] = supplierAdvanceScenario();

    $advance = createSupplierAdvance($user, $company, $supplier, [
        'amount' => 400,
        'payment_method' => 'check',
        'reference' => 'CH-9981',
        'bank_account' => 'Banco Nacional 0102',
    ]);

    $response = moveSupplierAdvanceTo($user, $company, $advance, 'pending_confirmation');

    $response->assertRedirect(route('supplier-advances.show', ['company' => $company->id, 'id' => $advance->id]));
    $response->assertSessionHasNoErrors();

    expect($advance->refresh()->status)->toBe('pending_confirmation');

    $payment = mirrorPaymentOf($advance);
    expect($payment)->not->toBeNull();
    expect($payment->code)->toBe('PGP000001');
    expect($payment->status)->toBe('draft');
    expect($payment->origin_type)->toBe('advance');
    expect($payment->origin_id)->toBe($advance->id);
    expect($payment->supplier_id)->toBe($supplier->id);
    expect($payment->payment_method)->toBe('check');
    expect($payment->reference)->toBe('CH-9981');
    expect($payment->bank_account)->toBe('Banco Nacional 0102');
    expect((float) $payment->amount)->toBe(400.0);
    expect((float) $payment->exchange_rate)->toBe((float) $advance->exchange_rate);
    /** Nace sin aplicaciones: el anticipo no cancela ninguna factura. */
    expect((float) $payment->applied_amount)->toBe(0.0);
    expect((float) $payment->unapplied_amount)->toBe(400.0);
});

/** Comprometido, no entregado: el crédito no existe hasta que el pago se confirme. */
test('an approved advance does not give credit to the supplier yet', function () {
    [$user, $company, $supplier] = supplierAdvanceScenario();

    $advance = createSupplierAdvance($user, $company, $supplier);
    moveSupplierAdvanceTo($user, $company, $advance, 'pending_confirmation')->assertSessionHasNoErrors();

    expect((float) $supplier->refresh()->advance_balance)->toBe(0.0);
});

test('confirming the mirror payment delivers the advance and credits the supplier', function () {
    [$user, $company, $supplier] = supplierAdvanceScenario();

    $advance = confirmedSupplierAdvance($user, $company, $supplier, ['amount' => 400]);

    expect($advance->status)->toBe('confirmed');
    expect((float) $advance->balance)->toBe(400.0);
    expect((float) $supplier->refresh()->advance_balance)->toBe(400.0);
    /** El anticipo no cancela deuda por sí solo: no toca el saldo por pagar. */
    expect((float) $supplier->current_balance)->toBe(0.0);
});

test('cancelling the mirror payment sends the advance back to draft', function () {
    [$user, $company, $supplier] = supplierAdvanceScenario();

    $advance = confirmedSupplierAdvance($user, $company, $supplier, ['amount' => 400]);
    $payment = mirrorPaymentOf($advance);

    moveSupplierPaymentTo($user, $company, $payment, 'cancelled', [
        'cancellation_reason' => 'El cheque fue devuelto por el banco.',
    ])->assertSessionHasNoErrors();

    $advance->refresh();
    expect($advance->status)->toBe('draft');
    /** Conserva su código: es el mismo anticipo, corregido. */
    expect($advance->code)->toBe('ANP000001');
    expect((float) $supplier->refresh()->advance_balance)->toBe(0.0);
});

test('cancelling a draft mirror payment also frees the advance', function () {
    [$user, $company, $supplier] = supplierAdvanceScenario();

    $advance = createSupplierAdvance($user, $company, $supplier);
    moveSupplierAdvanceTo($user, $company, $advance, 'pending_confirmation')->assertSessionHasNoErrors();

    moveSupplierPaymentTo($user, $company, mirrorPaymentOf($advance), 'cancelled', [
        'cancellation_reason' => 'La transferencia no se llegó a hacer.',
    ])->assertSessionHasNoErrors();

    expect($advance->refresh()->status)->toBe('draft');
    expect((float) $supplier->refresh()->advance_balance)->toBe(0.0);
});

test('an advance back in draft can be approved again with a new payment', function () {
    [$user, $company, $supplier] = supplierAdvanceScenario();

    $advance = createSupplierAdvance($user, $company, $supplier);
    moveSupplierAdvanceTo($user, $company, $advance, 'pending_confirmation')->assertSessionHasNoErrors();

    $first = mirrorPaymentOf($advance);
    moveSupplierPaymentTo($user, $company, $first, 'cancelled', [
        'cancellation_reason' => 'Se pagó por otra vía.',
    ])->assertSessionHasNoErrors();

    moveSupplierAdvanceTo($user, $company, $advance, 'pending_confirmation')->assertSessionHasNoErrors();

    expect($advance->refresh()->status)->toBe('pending_confirmation');

    $payments = SupplierPayment::query()
        ->where('origin_id', $advance->id)
        ->where('status', '!=', 'cancelled')
        ->get();

    expect($payments)->toHaveCount(1);
    expect($payments->first()->id)->not->toBe($first->id);
});

test('cancelling the advance cancels its mirror payment', function () {
    [$user, $company, $supplier] = supplierAdvanceScenario();

    $advance = createSupplierAdvance($user, $company, $supplier);
    moveSupplierAdvanceTo($user, $company, $advance, 'pending_confirmation')->assertSessionHasNoErrors();

    moveSupplierAdvanceTo($user, $company, $advance, 'cancelled')->assertSessionHasNoErrors();

    $advance->refresh();
    expect($advance->status)->toBe('cancelled');
    expect($advance->cancelled_at)->not->toBeNull();

    $payment = mirrorPaymentOf($advance);
    expect($payment->status)->toBe('cancelled');
    expect($payment->cancellation_reason)->toBe("El anticipo {$advance->code} fue anulado.");
});

test('a draft advance is cancelled without any payment behind it', function () {
    [$user, $company, $supplier] = supplierAdvanceScenario();

    $advance = createSupplierAdvance($user, $company, $supplier);

    moveSupplierAdvanceTo($user, $company, $advance, 'cancelled')->assertSessionHasNoErrors();

    expect($advance->refresh()->status)->toBe('cancelled');
    expect(SupplierPayment::count())->toBe(0);
});

test('a delivered advance is no longer cancelled from its own screen', function () {
    [$user, $company, $supplier] = supplierAdvanceScenario();

    $advance = confirmedSupplierAdvance($user, $company, $supplier);

    moveSupplierAdvanceTo($user, $company, $advance, 'cancelled')
        ->assertSessionHasErrors('status');

    expect($advance->refresh()->status)->toBe('confirmed');
});

test('an advance with credit already applied cannot be cancelled', function () {
    [$user, $company, $supplier] = supplierAdvanceScenario();

    $advance = createSupplierAdvance($user, $company, $supplier);
    $advance->update(['applied_amount' => 100, 'balance' => 300]);

    moveSupplierAdvanceTo($user, $company, $advance, 'cancelled')
        ->assertSessionHasErrors('status');

    expect($advance->refresh()->status)->toBe('draft');
});

test('the screen cannot confirm an advance by itself', function () {
    [$user, $company, $supplier] = supplierAdvanceScenario();

    $advance = createSupplierAdvance($user, $company, $supplier);
    moveSupplierAdvanceTo($user, $company, $advance, 'pending_confirmation')->assertSessionHasNoErrors();

    moveSupplierAdvanceTo($user, $company, $advance, 'confirmed')
        ->assertSessionHasErrors('status');

    expect($advance->refresh()->status)->toBe('pending_confirmation');
    expect((float) $supplier->refresh()->advance_balance)->toBe(0.0);
});

test('a cancelled advance is a dead end', function () {
    [$user, $company, $supplier] = supplierAdvanceScenario();

    $advance = SupplierAdvance::factory()->cancelled()->create([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'created_by' => $user->id,
    ]);

    moveSupplierAdvanceTo($user, $company, $advance, 'pending_confirmation')
        ->assertSessionHasErrors('status');

    expect($advance->refresh()->status)->toBe('cancelled');
});

test('the mirror payment is not editable', function () {
    [$user, $company, $supplier] = supplierAdvanceScenario();

    $advance = createSupplierAdvance($user, $company, $supplier);
    moveSupplierAdvanceTo($user, $company, $advance, 'pending_confirmation')->assertSessionHasNoErrors();

    $payment = mirrorPaymentOf($advance);

    \Pest\Laravel\actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->put(
            route('supplier-payments.update', ['company' => $company->id, 'id' => $payment->id]),
            [
                'supplier_id' => $supplier->id,
                'payment_date' => now()->toDateString(),
                'payment_method' => 'cash',
                'currency' => 'USD',
                'amount' => 999,
                'applications' => [],
            ],
        )
        ->assertSessionHasErrors('origin_type');

    expect((float) $payment->refresh()->amount)->toBe(400.0);
});

test('a user without permission cannot change the status', function () {
    [$user, $company, $supplier] = supplierAdvanceScenario();

    $advance = createSupplierAdvance($user, $company, $supplier);

    assignRoleWithPermissions($user, $company, ['supplier-advances.list']);

    moveSupplierAdvanceTo($user, $company, $advance, 'pending_confirmation')->assertForbidden();
});
