<?php

declare(strict_types=1);

use App\Modules\Configuration\Models\Configuration;
use App\Modules\SupplierPayment\Models\SupplierPayment;

use function Pest\Laravel\actingAs;

test('the payment freezes the catalog rate without the form sending it', function () {
    [$user, $company, $supplier] = supplierPaymentScenario();

    $payment = createSupplierPayment($user, $company, $supplier);

    expect((float) $payment->exchange_rate)->toBe(36.5);
    expect($payment->currency)->toBe('USD');
    expect($payment->base_currency)->toBe('USD');
    expect((float) $payment->base_exchange_rate)->toBe(36.5);
    expect((float) $payment->amount_ves)->toBe(9125.0);
});

test('a payment in another currency also freezes the company rate', function () {
    [$user, $company, $supplier] = supplierPaymentScenario();

    todayExchangeRate($company, $user, 'EUR', 40.0);

    $payment = createSupplierPayment($user, $company, $supplier, [
        'currency' => 'EUR',
        'amount' => 100,
    ]);

    expect($payment->currency)->toBe('EUR');
    expect((float) $payment->exchange_rate)->toBe(40.0);
    expect($payment->base_currency)->toBe('USD');
    expect((float) $payment->base_exchange_rate)->toBe(36.5);
    expect((float) $payment->amount_ves)->toBe(4000.0);
});

test('a currency without a loaded rate is rejected', function () {
    [$user, $company, $supplier] = supplierPaymentScenario();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('supplier-payments.store', ['company' => $company->id]),
            supplierPaymentPayload($supplier, ['currency' => 'EUR']),
        )
        ->assertSessionHasErrors('exchange_rate');

    expect(SupplierPayment::count())->toBe(0);
});

test('the rate typed in the form is ignored when the company does not allow overriding', function () {
    [$user, $company, $supplier] = supplierPaymentScenario();

    Configuration::query()
        ->where('company_id', $company->id)
        ->update(['allows_rate_override' => 'no']);

    app()->forgetScopedInstances();

    $payment = createSupplierPayment($user, $company, $supplier, ['exchange_rate' => 99]);

    expect((float) $payment->exchange_rate)->toBe(36.5);
});

test('the rate typed in the form wins when the company allows overriding', function () {
    [$user, $company, $supplier] = supplierPaymentScenario();

    $payment = createSupplierPayment($user, $company, $supplier, ['exchange_rate' => 40]);

    expect((float) $payment->exchange_rate)->toBe(40.0);
    expect((float) $payment->amount_ves)->toBe(10000.0);
});

test('saving the draft again refreshes the rate', function () {
    [$user, $company, $supplier] = supplierPaymentScenario();

    $payment = createSupplierPayment($user, $company, $supplier);
    expect((float) $payment->exchange_rate)->toBe(36.5);

    \App\Modules\ExchangeRate\Models\ExchangeRate::query()
        ->where('company_id', $company->id)
        ->where('currency', 'USD')
        ->update(['rate' => 42.0]);

    app()->forgetScopedInstances();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(
            route('supplier-payments.update', ['company' => $company->id, 'id' => $payment->id]),
            supplierPaymentPayload($supplier),
        )
        ->assertSessionHasNoErrors();

    expect((float) $payment->refresh()->exchange_rate)->toBe(42.0);
    expect((float) $payment->amount_ves)->toBe(10500.0);
});
