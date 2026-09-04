<?php

declare(strict_types=1);

use function Pest\Laravel\actingAs;

/**
 * Cerrar la factura de compra no es una decisión: es no deberle nada al
 * proveedor. Lo escriben los pagos, no la pantalla.
 */

/** Mueve el estado de la factura de compra por la ruta del usuario. */
function putPurchaseInvoiceStatus(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    \App\Modules\PurchaseInvoice\Models\PurchaseInvoice $invoice,
    array $payload,
): \Illuminate\Testing\TestResponse {
    return actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(
            route('purchase-invoices.update-status', ['company' => $company->id, 'id' => $invoice->id]),
            $payload,
        );
}

test('the screen cannot declare a confirmed invoice completed', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseInvoiceScenario();

    $invoice = payablePurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);

    putPurchaseInvoiceStatus($user, $company, $invoice, ['status' => 'completed'])
        ->assertSessionHasErrors('status');

    expect($invoice->refresh()->status)->toBe('confirmed');
});

test('a partial payment leaves the invoice open', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseInvoiceScenario();

    $invoice = payablePurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);
    $total = (float) $invoice->total;

    $payment = createSupplierPayment($user, $company, $supplier, [
        'amount' => $total - 50,
        'applications' => [
            ['purchase_invoice_id' => $invoice->id, 'applied_amount' => $total - 50],
        ],
    ]);

    moveSupplierPaymentTo($user, $company, $payment, 'confirmed')->assertSessionHasNoErrors();

    $invoice->refresh();
    expect($invoice->payment_status)->toBe('partial');
    expect($invoice->status)->toBe('confirmed');
});

test('paying the invoice in full closes it on its own', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseInvoiceScenario();

    $invoice = payablePurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);
    $total = (float) $invoice->total;

    $payment = createSupplierPayment($user, $company, $supplier, [
        'amount' => $total,
        'applications' => [
            ['purchase_invoice_id' => $invoice->id, 'applied_amount' => $total],
        ],
    ]);

    moveSupplierPaymentTo($user, $company, $payment, 'confirmed')->assertSessionHasNoErrors();

    $invoice->refresh();
    expect($invoice->payment_status)->toBe('paid');
    expect((float) $invoice->balance)->toBe(0.0);
    expect($invoice->status)->toBe('completed');
});

test('reverting the payment that settled the invoice reopens it', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseInvoiceScenario();

    $invoice = payablePurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);
    $total = (float) $invoice->total;

    $payment = createSupplierPayment($user, $company, $supplier, [
        'amount' => $total,
        'applications' => [
            ['purchase_invoice_id' => $invoice->id, 'applied_amount' => $total],
        ],
    ]);

    moveSupplierPaymentTo($user, $company, $payment, 'confirmed')->assertSessionHasNoErrors();
    expect($invoice->refresh()->status)->toBe('completed');

    moveSupplierPaymentTo($user, $company, $payment, 'cancelled', [
        'cancellation_reason' => 'El pago se registró dos veces.',
    ])->assertSessionHasNoErrors();

    /** Vuelve a deber, así que vuelve a estar abierta. */
    $invoice->refresh();
    expect((float) $invoice->balance)->toBe($total);
    expect($invoice->status)->toBe('confirmed');
});
