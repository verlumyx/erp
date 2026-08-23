<?php

declare(strict_types=1);

use App\Modules\Configuration\Models\Configuration;
use App\Modules\ExchangeRate\Models\ExchangeRate;
use App\Modules\SupplierAdvance\Models\SupplierAdvance;

use function Pest\Laravel\actingAs;

test('the advance freezes the catalog rate without the form sending it', function () {
    [$user, $company, $supplier] = supplierAdvanceScenario();

    $advance = createSupplierAdvance($user, $company, $supplier);

    expect($advance->currency)->toBe('USD');
    expect((float) $advance->exchange_rate)->toBe(36.5);
    expect($advance->base_currency)->toBe('USD');
    expect((float) $advance->base_exchange_rate)->toBe(36.5);
});

test('an advance in another currency also freezes the company rate', function () {
    [$user, $company, $supplier] = supplierAdvanceScenario();

    todayExchangeRate($company, $user, 'EUR', 40.0);

    $advance = createSupplierAdvance($user, $company, $supplier, [
        'currency' => 'EUR',
        'amount' => 100,
    ]);

    expect($advance->currency)->toBe('EUR');
    expect((float) $advance->exchange_rate)->toBe(40.0);
    expect($advance->base_currency)->toBe('USD');
    expect((float) $advance->base_exchange_rate)->toBe(36.5);
});

test('a currency without a loaded rate is rejected', function () {
    [$user, $company, $supplier] = supplierAdvanceScenario();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(
            route('supplier-advances.store', ['company' => $company->id]),
            supplierAdvancePayload($supplier, ['currency' => 'EUR']),
        )
        ->assertSessionHasErrors('exchange_rate');

    expect(SupplierAdvance::count())->toBe(0);
});

test('the rate typed in the form is ignored when the company does not allow overriding', function () {
    [$user, $company, $supplier] = supplierAdvanceScenario();

    Configuration::query()
        ->where('company_id', $company->id)
        ->update(['allows_rate_override' => 'no']);

    app()->forgetScopedInstances();

    $advance = createSupplierAdvance($user, $company, $supplier, ['exchange_rate' => 99]);

    expect((float) $advance->exchange_rate)->toBe(36.5);
});

test('the rate typed in the form wins when the company allows overriding', function () {
    [$user, $company, $supplier] = supplierAdvanceScenario();

    $advance = createSupplierAdvance($user, $company, $supplier, ['exchange_rate' => 40]);

    expect((float) $advance->exchange_rate)->toBe(40.0);
});

test('saving the draft again refreshes the rate', function () {
    [$user, $company, $supplier] = supplierAdvanceScenario();

    $advance = createSupplierAdvance($user, $company, $supplier);
    expect((float) $advance->exchange_rate)->toBe(36.5);

    ExchangeRate::query()
        ->where('company_id', $company->id)
        ->where('currency', 'USD')
        ->update(['rate' => 42.0]);

    app()->forgetScopedInstances();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(
            route('supplier-advances.update', ['company' => $company->id, 'id' => $advance->id]),
            supplierAdvancePayload($supplier),
        )
        ->assertSessionHasNoErrors();

    expect((float) $advance->refresh()->exchange_rate)->toBe(42.0);
});

/**
 * El pago espejo copia la tasa del anticipo, no la del día en que se aprueba:
 * el dinero comprometido vale lo que valía cuando se capturó.
 */
test('the mirror payment copies the frozen rate of the advance', function () {
    [$user, $company, $supplier] = supplierAdvanceScenario();

    $advance = createSupplierAdvance($user, $company, $supplier, ['amount' => 400]);

    ExchangeRate::query()
        ->where('company_id', $company->id)
        ->where('currency', 'USD')
        ->update(['rate' => 99.0]);

    app()->forgetScopedInstances();

    moveSupplierAdvanceTo($user, $company, $advance, 'pending_confirmation')->assertSessionHasNoErrors();

    $payment = \App\Modules\SupplierPayment\Models\SupplierPayment::query()
        ->where('origin_id', $advance->id)
        ->firstOrFail();

    expect((float) $payment->exchange_rate)->toBe(36.5);
    expect($payment->base_currency)->toBe('USD');
    expect((float) $payment->base_exchange_rate)->toBe(36.5);
    /** El pago tiene valor legal: su importe en bolívares queda escrito. */
    expect((float) $payment->amount_ves)->toBe(14600.0);
});
