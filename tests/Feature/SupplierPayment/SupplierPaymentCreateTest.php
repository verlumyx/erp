<?php

declare(strict_types=1);

use App\Modules\Supplier\Models\Supplier;
use App\Modules\SupplierPayment\Models\SupplierPayment;
use App\Modules\SupplierPayment\Models\SupplierPaymentApplication;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

test('a supplier payment can be created', function () {
    [$user, $company, $supplier] = supplierPaymentScenario();

    $payload = supplierPaymentPayload($supplier, [
        'reference' => '0102-9987',
        'bank_account' => 'Banco Nacional 0102',
        'notes' => 'Transferencia del viernes.',
    ]);

    $response = actingAs($user)
        ->withSession(['current_company_id' => $company->id])
        ->post(route('supplier-payments.store', ['company' => $company->id]), $payload);

    $response->assertRedirect(route('supplier-payments.index', ['company' => $company->id]));
    $response->assertSessionHasNoErrors();

    $payment = SupplierPayment::find($payload['id']);
    expect($payment)->not->toBeNull();
    expect($payment->code)->toBe('PGP000001');
    expect($payment->status)->toBe('draft');
    expect($payment->company_id)->toBe($company->id);
    expect($payment->created_by)->toBe($user->id);
    expect($payment->supplier_id)->toBe($supplier->id);
    expect($payment->origin_type)->toBe('supplier');
    expect($payment->origin_id)->toBeNull();
    expect($payment->reference)->toBe('0102-9987');
    expect((float) $payment->amount)->toBe(250.0);
    expect((float) $payment->applied_amount)->toBe(0.0);
    expect((float) $payment->unapplied_amount)->toBe(250.0);
});

test('the payment distributes its amount among the invoices of the supplier', function () {
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

    expect((float) $payment->applied_amount)->toBe(300.0);
    expect((float) $payment->unapplied_amount)->toBe(0.0);
    expect($payment->applications)->toHaveCount(2);

    /** Escribir el reparto todavía no mueve el saldo: eso lo hace confirmar. */
    expect((float) $first->refresh()->balance)->toBe(250.0);
    expect((float) $second->refresh()->balance)->toBe(250.0);
});

test('a payment started from an invoice freezes that invoice as its origin', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = supplierPaymentScenario();

    $invoice = payablePurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);

    $payment = createSupplierPayment($user, $company, $supplier, [
        'origin_type' => 'invoice',
        'origin_id' => $invoice->id,
        'amount' => 250,
        'applications' => [
            ['purchase_invoice_id' => $invoice->id, 'applied_amount' => 250],
        ],
    ]);

    expect($payment->origin_type)->toBe('invoice');
    expect($payment->origin_id)->toBe($invoice->id);
});

test('a payment started from an invoice needs that invoice', function () {
    [$user, $company, $supplier] = supplierPaymentScenario();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('supplier-payments.store', ['company' => $company->id]),
            supplierPaymentPayload($supplier, ['origin_type' => 'invoice']),
        )
        ->assertSessionHasErrors('origin_id');
});

test('the mirror payment of an advance is not created from this screen', function () {
    [$user, $company, $supplier] = supplierPaymentScenario();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('supplier-payments.store', ['company' => $company->id]),
            supplierPaymentPayload($supplier, ['origin_type' => 'advance']),
        )
        ->assertSessionHasErrors('origin_type');

    expect(SupplierPayment::count())->toBe(0);
});

test('a payment does not cross suppliers', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = supplierPaymentScenario();

    $other = Supplier::factory()->create(['company_id' => $company->id]);
    $invoice = payablePurchaseInvoice($user, $company, $other, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('supplier-payments.store', ['company' => $company->id]),
            supplierPaymentPayload($supplier, [
                'applications' => [
                    ['purchase_invoice_id' => $invoice->id, 'applied_amount' => 100],
                ],
            ]),
        )
        ->assertSessionHasErrors('applications.0.purchase_invoice_id');

    expect(SupplierPayment::count())->toBe(0);
});

test('a draft invoice does not admit payments yet', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = supplierPaymentScenario();

    $invoice = createPurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('supplier-payments.store', ['company' => $company->id]),
            supplierPaymentPayload($supplier, [
                'applications' => [
                    ['purchase_invoice_id' => $invoice->id, 'applied_amount' => 100],
                ],
            ]),
        )
        ->assertSessionHasErrors('applications.0.purchase_invoice_id');
});

test('an application cannot exceed the balance of its invoice', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = supplierPaymentScenario();

    $invoice = payablePurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('supplier-payments.store', ['company' => $company->id]),
            supplierPaymentPayload($supplier, [
                'amount' => 500,
                'applications' => [
                    ['purchase_invoice_id' => $invoice->id, 'applied_amount' => 300],
                ],
            ]),
        )
        ->assertSessionHasErrors('applications.0.applied_amount');
});

test('the distribution cannot exceed the amount of the payment', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = supplierPaymentScenario();

    $invoice = payablePurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('supplier-payments.store', ['company' => $company->id]),
            supplierPaymentPayload($supplier, [
                'amount' => 100,
                'applications' => [
                    ['purchase_invoice_id' => $invoice->id, 'applied_amount' => 250],
                ],
            ]),
        )
        ->assertSessionHasErrors('applications');
});

/**
 * La retención cancela deuda sin salir del banco, así que el reparto puede
 * llegar hasta el monto entregado más lo retenido.
 */
test('the withholding widens what the payment can settle', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = supplierPaymentScenario();

    $invoice = payablePurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);

    $payment = createSupplierPayment($user, $company, $supplier, [
        'amount' => 200,
        'withholding_amount' => 50,
        'applications' => [
            ['purchase_invoice_id' => $invoice->id, 'applied_amount' => 250],
        ],
    ]);

    expect((float) $payment->applied_amount)->toBe(250.0);
    expect((float) $payment->unapplied_amount)->toBe(0.0);
});

test('the same invoice cannot appear twice in the distribution', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = supplierPaymentScenario();

    $invoice = payablePurchaseInvoice($user, $company, $supplier, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('supplier-payments.store', ['company' => $company->id]),
            supplierPaymentPayload($supplier, [
                'applications' => [
                    ['purchase_invoice_id' => $invoice->id, 'applied_amount' => 100],
                    ['purchase_invoice_id' => $invoice->id, 'applied_amount' => 100],
                ],
            ]),
        )
        ->assertSessionHasErrors('applications.1.purchase_invoice_id');

    expect(SupplierPaymentApplication::count())->toBe(0);
});

test('the amount of the payment is required and positive', function () {
    [$user, $company, $supplier] = supplierPaymentScenario();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('supplier-payments.store', ['company' => $company->id]),
            supplierPaymentPayload($supplier, ['amount' => 0]),
        )
        ->assertSessionHasErrors('amount');
});

test('the code is sequential per company', function () {
    [$user, $company, $supplier] = supplierPaymentScenario();

    $first = createSupplierPayment($user, $company, $supplier);
    $second = createSupplierPayment($user, $company, $supplier);

    expect($first->code)->toBe('PGP000001');
    expect($second->code)->toBe('PGP000002');
});

test('the create form is rendered', function () {
    [$user, $company] = supplierPaymentScenario();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->get(route('supplier-payments.create', ['company' => $company->id]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('supplier-payments/create'));
});

test('a user without permission cannot create a payment', function () {
    [$user, $company, $supplier] = supplierPaymentScenario();

    assignRoleWithPermissions($user, $company, ['supplier-payments.list']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('supplier-payments.store', ['company' => $company->id]),
            supplierPaymentPayload($supplier),
        )
        ->assertForbidden();
});
