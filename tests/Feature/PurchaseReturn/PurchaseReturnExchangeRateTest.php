<?php

declare(strict_types=1);

use App\Modules\Configuration\Models\Configuration;
use App\Modules\ExchangeRate\Models\ExchangeRate;
use App\Modules\PurchaseReturn\Models\PurchaseReturn;

use function Pest\Laravel\actingAs;

test('the return freezes the catalog rate without the form sending it', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    $payload = purchaseReturnPayload($supplier, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('purchase-returns.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    $return = PurchaseReturn::find($payload['id']);

    expect($return->currency)->toBe('USD');
    expect((float) $return->exchange_rate)->toBe(36.5);
    expect($return->base_currency)->toBe('USD');
    expect((float) $return->base_exchange_rate)->toBe(36.5);
});

test('a return in another currency also freezes the company rate', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    todayExchangeRate($company, $user, 'EUR', 40.0);

    $payload = purchaseReturnPayload($supplier, $warehouse, $item, $unit, ['currency' => 'EUR']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('purchase-returns.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    $return = PurchaseReturn::find($payload['id']);

    expect((float) $return->exchange_rate)->toBe(40.0);
    expect($return->base_currency)->toBe('USD');
    expect((float) $return->base_exchange_rate)->toBe(36.5);
});

test('a return in a currency without a loaded rate is not issued', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    $payload = purchaseReturnPayload($supplier, $warehouse, $item, $unit, ['currency' => 'EUR']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('purchase-returns.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('exchange_rate');

    expect(PurchaseReturn::find($payload['id']))->toBeNull();
});

test('the rate typed in the form is ignored when the company forbids correcting it', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    Configuration::query()
        ->where('company_id', $company->id)
        ->update(['allows_rate_override' => 'no']);

    $payload = purchaseReturnPayload($supplier, $warehouse, $item, $unit, ['exchange_rate' => 1]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('purchase-returns.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    expect((float) PurchaseReturn::find($payload['id'])->exchange_rate)->toBe(36.5);
});

test('the rate typed in the form wins when the company allows correcting it', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    $payload = purchaseReturnPayload($supplier, $warehouse, $item, $unit, ['exchange_rate' => 38.25]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('purchase-returns.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    expect((float) PurchaseReturn::find($payload['id'])->exchange_rate)->toBe(38.25);
});

test('saving the draft again refreshes the rate', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    $payload = purchaseReturnPayload($supplier, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('purchase-returns.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    ExchangeRate::query()
        ->where('company_id', $company->id)
        ->where('currency', 'USD')
        ->update(['rate' => 37.8]);

    /**
     * El resolver cachea por request y el test reutiliza la misma aplicación:
     * en producción cada petición estrena su propio caché.
     */
    app()->forgetScopedInstances();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(route('purchase-returns.update', ['company' => $company->id, 'id' => $payload['id']]), $payload)
        ->assertSessionHasNoErrors();

    expect((float) PurchaseReturn::find($payload['id'])->exchange_rate)->toBe(37.8);
});

/**
 * El formulario enseña la tasa del catálogo en el campo, pero la manda vacía
 * mientras el usuario no la corrija: vacía significa «resuélvela tú».
 */
test('an empty rate is not a manual correction', function () {
    [$user, $company, $supplier, $warehouse, $item, $unit] = purchaseReturnScenario();

    $payload = purchaseReturnPayload($supplier, $warehouse, $item, $unit, ['exchange_rate' => '']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('purchase-returns.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    expect((float) PurchaseReturn::find($payload['id'])->exchange_rate)->toBe(36.5);
});
