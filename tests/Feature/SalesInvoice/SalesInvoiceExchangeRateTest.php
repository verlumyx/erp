<?php

declare(strict_types=1);

use App\Modules\Configuration\Models\Configuration;
use App\Modules\ExchangeRate\Models\ExchangeRate;
use App\Modules\SalesInvoice\Models\SalesInvoice;

use function Pest\Laravel\actingAs;

/**
 * La tasa de una factura sale del catálogo, no del formulario: es el documento
 * con valor legal y su equivalente en bolívares queda congelado con él.
 */
test('the invoice freezes the catalog rate without the form sending it', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesInvoiceScenario();

    $payload = salesInvoicePayload($client, $warehouse, $item, $unit);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('sales-invoices.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    $invoice = SalesInvoice::find($payload['id']);

    expect($invoice->currency)->toBe('USD');
    expect((float) $invoice->exchange_rate)->toBe(36.5);
    expect($invoice->base_currency)->toBe('USD');
    expect((float) $invoice->base_exchange_rate)->toBe(36.5);
});

test('an invoice in another currency also freezes the company rate', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesInvoiceScenario();

    todayExchangeRate($company, $user, 'EUR', 40.0);

    $payload = salesInvoicePayload($client, $warehouse, $item, $unit, ['currency' => 'EUR']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('sales-invoices.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    $invoice = SalesInvoice::find($payload['id']);

    expect((float) $invoice->exchange_rate)->toBe(40.0);
    expect($invoice->base_currency)->toBe('USD');
    expect((float) $invoice->base_exchange_rate)->toBe(36.5);
    // 200 EUR a 40,00 son 8.000 bolívares.
    expect((float) $invoice->total_ves)->toBe(8000.0);
});

test('an invoice in a currency without a loaded rate is not issued', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesInvoiceScenario();

    $payload = salesInvoicePayload($client, $warehouse, $item, $unit, ['currency' => 'EUR']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('sales-invoices.store', ['company' => $company->id]), $payload)
        ->assertSessionHasErrors('exchange_rate');

    expect(SalesInvoice::find($payload['id']))->toBeNull();
});

test('the rate typed in the form is ignored when the company forbids correcting it', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesInvoiceScenario();

    Configuration::query()
        ->where('company_id', $company->id)
        ->update(['allows_rate_override' => 'no']);

    $payload = salesInvoicePayload($client, $warehouse, $item, $unit, ['exchange_rate' => 1]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('sales-invoices.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    expect((float) SalesInvoice::find($payload['id'])->exchange_rate)->toBe(36.5);
});

test('the rate typed in the form wins when the company allows correcting it', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesInvoiceScenario();

    $payload = salesInvoicePayload($client, $warehouse, $item, $unit, ['exchange_rate' => 38.25]);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('sales-invoices.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    $invoice = SalesInvoice::find($payload['id']);

    expect((float) $invoice->exchange_rate)->toBe(38.25);
    expect((float) $invoice->total_ves)->toBe(7650.0);
});

test('saving the draft again refreshes the rate and the bolivar amounts', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesInvoiceScenario();

    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit);
    expect((float) $invoice->exchange_rate)->toBe(36.5);

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
            route('sales-invoices.update', ['company' => $company->id, 'id' => $invoice->id]),
            salesInvoicePayload($client, $warehouse, $item, $unit),
        )
        ->assertSessionHasNoErrors();

    $invoice->refresh();

    expect((float) $invoice->exchange_rate)->toBe(37.8);
    expect((float) $invoice->total_ves)->toBe(7560.0);
});

/**
 * Emitir no vuelve a resolver la tasa: es justo el momento en que queda
 * congelada, y recalcularla reescribiría el valor legal del documento.
 */
test('confirming the invoice never re-resolves its rate', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesInvoiceScenario();

    $invoice = createSalesInvoice($user, $company, $client, $warehouse, $item, $unit);

    ExchangeRate::query()
        ->where('company_id', $company->id)
        ->where('currency', 'USD')
        ->update(['rate' => 50]);

    app()->forgetScopedInstances();

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->put(
            route('sales-invoices.update-status', ['company' => $company->id, 'id' => $invoice->id]),
            ['status' => 'confirmed'],
        )
        ->assertSessionHasNoErrors();

    $invoice->refresh();

    expect((float) $invoice->exchange_rate)->toBe(36.5);
    expect((float) $invoice->total_ves)->toBe(7300.0);
});

/**
 * El formulario enseña la tasa del catálogo en el campo, pero la manda vacía
 * mientras el usuario no la corrija: vacía significa «resuélvela tú».
 */
test('an empty rate is not a manual correction', function () {
    [$user, $company, $client, $warehouse, $item, $unit] = salesInvoiceScenario();

    $payload = salesInvoicePayload($client, $warehouse, $item, $unit, ['exchange_rate' => '']);

    actingAs($user)->withSession(['current_company_id' => $company->id])
        ->post(route('sales-invoices.store', ['company' => $company->id]), $payload)
        ->assertSessionHasNoErrors();

    expect((float) SalesInvoice::find($payload['id'])->exchange_rate)->toBe(36.5);
});
