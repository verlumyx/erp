<?php

declare(strict_types=1);

use App\Modules\Configuration\Models\Configuration;
use App\Modules\ExchangeRate\Models\ExchangeRate;
use App\Modules\SalesOrder\Models\SalesOrder;

use function Pest\Laravel\actingAs;

/**
 * La tasa de un pedido sale del catálogo, no del formulario. Antes bastaba con
 * escribir 1 para registrar una venta en euros valorada como si fuera dólares.
 */
test('the order freezes the catalog rate without the form sending it', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    $payload = salesOrderPayload($client, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('sales-orders.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    $order = SalesOrder::find($payload['id']);

    expect($order->currency)->toBe('USD');
    expect((float) $order->exchange_rate)->toBe(36.5);
    expect($order->base_currency)->toBe('USD');
    expect((float) $order->base_exchange_rate)->toBe(36.5);
});

test('an order in another currency also freezes the company rate', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    todayExchangeRate($company, $user, 'EUR', 40.0);

    $payload = salesOrderPayload($client, $warehouse, $item, $unit, ['currency' => 'EUR']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('sales-orders.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    $order = SalesOrder::find($payload['id']);

    expect((float) $order->exchange_rate)->toBe(40.0);
    expect($order->base_currency)->toBe('USD');
    expect((float) $order->base_exchange_rate)->toBe(36.5);
});

test('an order in a currency without a loaded rate is not issued', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    $payload = salesOrderPayload($client, $warehouse, $item, $unit, ['currency' => 'EUR']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('sales-orders.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('exchange_rate');

    expect(SalesOrder::find($payload['id']))->toBeNull();
});

test('the rate typed in the form is ignored when the company forbids correcting it', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    Configuration::query()
        ->where('company_id', $company->id)
        ->update(['allows_rate_override' => 'no']);

    $payload = salesOrderPayload($client, $warehouse, $item, $unit, ['exchange_rate' => 1]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('sales-orders.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    expect((float) SalesOrder::find($payload['id'])->exchange_rate)->toBe(36.5);
});

test('the rate typed in the form wins when the company allows correcting it', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    $payload = salesOrderPayload($client, $warehouse, $item, $unit, ['exchange_rate' => 38.25]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('sales-orders.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    expect((float) SalesOrder::find($payload['id'])->exchange_rate)->toBe(38.25);
});

test('saving the draft again refreshes the rate', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesOrderScenario();

    $order = createSalesOrder($user, $company, $client, $warehouse, $item, $unit);
    expect((float) $order->exchange_rate)->toBe(36.5);

    /** El emisor corrige la tasa del día: el borrador la estrena al guardarse. */
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
        ->put(
            route('sales-orders.update', ['company' => $company->id, 'id' => $order->id]),
            salesOrderPayload($client, $warehouse, $item, $unit),
        )
        ->assertSessionHasNoErrors();

    expect((float) SalesOrder::find($order->id)->exchange_rate)->toBe(37.8);
});
