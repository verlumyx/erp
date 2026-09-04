<?php

declare(strict_types=1);

use function Pest\Laravel\actingAs;

/**
 * Cerrar la factura de venta no es una decisión: es que el cliente ya no deba
 * nada. Lo escriben los cobros, no la pantalla.
 */

/** Mueve el estado de la factura de venta por la ruta del usuario. */
function putSalesInvoiceStatus(
    \App\Modules\User\Models\User $user,
    \App\Modules\Company\Models\Company $company,
    \App\Modules\SalesInvoice\Models\SalesInvoice $invoice,
    array $payload,
): \Illuminate\Testing\TestResponse {
    return actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(
            route('sales-invoices.update-status', ['company' => $company->id, 'id' => $invoice->id]),
            $payload,
        );
}

test('the screen cannot declare an issued invoice completed', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesInvoiceScenario();

    $invoice = collectibleSalesInvoice($user, $company, $client, $warehouse, $item, $unit);

    putSalesInvoiceStatus($user, $company, $invoice, ['status' => 'completed'])
        ->assertSessionHasErrors('status');

    expect($invoice->refresh()->status)->toBe('confirmed');
});

test('a partial collection leaves the invoice open', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesInvoiceScenario();

    $invoice = collectibleSalesInvoice($user, $company, $client, $warehouse, $item, $unit);
    $total = (float) $invoice->total;

    $collection = createClientCollection($user, $company, $client, [
        'amount' => $total - 50,
        'applications' => [
            ['sales_invoice_id' => $invoice->id, 'applied_amount' => $total - 50],
        ],
    ]);

    moveClientCollectionTo($user, $company, $collection, 'confirmed')->assertSessionHasNoErrors();

    $invoice->refresh();
    expect($invoice->payment_status)->toBe('partial');
    expect($invoice->status)->toBe('confirmed');
});

test('collecting the invoice in full closes it on its own', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesInvoiceScenario();

    $invoice = collectibleSalesInvoice($user, $company, $client, $warehouse, $item, $unit);
    $total = (float) $invoice->total;

    $collection = createClientCollection($user, $company, $client, [
        'amount' => $total,
        'applications' => [
            ['sales_invoice_id' => $invoice->id, 'applied_amount' => $total],
        ],
    ]);

    moveClientCollectionTo($user, $company, $collection, 'confirmed')->assertSessionHasNoErrors();

    $invoice->refresh();
    expect($invoice->payment_status)->toBe('paid');
    expect((float) $invoice->balance)->toBe(0.0);
    expect($invoice->status)->toBe('completed');
});

test('reverting the collection that settled the invoice reopens it', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesInvoiceScenario();

    $invoice = collectibleSalesInvoice($user, $company, $client, $warehouse, $item, $unit);
    $total = (float) $invoice->total;

    $collection = createClientCollection($user, $company, $client, [
        'amount' => $total,
        'applications' => [
            ['sales_invoice_id' => $invoice->id, 'applied_amount' => $total],
        ],
    ]);

    moveClientCollectionTo($user, $company, $collection, 'confirmed')->assertSessionHasNoErrors();
    expect($invoice->refresh()->status)->toBe('completed');

    moveClientCollectionTo($user, $company, $collection, 'cancelled', [
        'cancellation_reason' => 'El cobro se registró dos veces.',
    ])->assertSessionHasNoErrors();

    /** Vuelve a ser una cuenta por cobrar, así que vuelve a estar abierta. */
    $invoice->refresh();
    expect((float) $invoice->balance)->toBe($total);
    expect($invoice->status)->toBe('confirmed');
});
