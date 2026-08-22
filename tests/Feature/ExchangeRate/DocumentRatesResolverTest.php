<?php

declare(strict_types=1);

use App\Modules\Configuration\Models\Configuration;
use App\Modules\ExchangeRate\Exceptions\ExchangeRateNotFoundException;
use App\Modules\ExchangeRate\Models\ExchangeRate;
use App\Modules\ExchangeRate\Services\Contracts\DocumentRatesResolverInterface;

/**
 * Qué congela un documento al emitirse: su propia moneda con su tasa, y la
 * moneda de la empresa con la suya. El segundo par es el que permite volver a
 * expresar el documento en la moneda de la empresa dentro de diez años.
 *
 * @param  array<string, mixed>  $configuration
 * @return array{0: \App\Modules\Company\Models\Company, 1: DocumentRatesResolverInterface}
 */
function documentRatesScenario(array $configuration = []): array
{
    [$user, $company] = createUserWithCompany();

    if ($configuration !== []) {
        Configuration::query()->where('company_id', $company->id)->update($configuration);
    }

    foreach (['USD' => 36.5, 'EUR' => 40.0] as $currency => $rate) {
        ExchangeRate::factory()->create([
            'company_id' => $company->id,
            'currency' => $currency,
            'rate_date' => '2026-08-16',
            'rate' => $rate,
            'type' => 'legal',
            'status' => 'active',
            'created_by' => $user->id,
        ]);
    }

    return [$company, app(DocumentRatesResolverInterface::class)];
}

test('it freezes both the document rate and the company rate', function () {
    [$company, $resolver] = documentRatesScenario();

    $rates = $resolver->forDocument($company->id, 'EUR', '2026-08-16');

    expect($rates->currency)->toBe('EUR');
    expect($rates->exchangeRate)->toBe(40.0);
    expect($rates->baseCurrency)->toBe('USD');
    expect($rates->baseExchangeRate)->toBe(36.5);
});

test('a document issued in the company currency carries the same rate twice', function () {
    [$company, $resolver] = documentRatesScenario();

    $rates = $resolver->forDocument($company->id, 'USD', '2026-08-16');

    expect($rates->exchangeRate)->toBe(36.5);
    expect($rates->baseExchangeRate)->toBe(36.5);
});

test('a company working in bolivares never carries a rate', function () {
    [$company, $resolver] = documentRatesScenario([
        'base_currency' => 'VES',
        'secondary_currency' => 'VES',
    ]);

    $rates = $resolver->forDocument($company->id, 'VES', '2026-08-16');

    expect($rates->exchangeRate)->toBe(1.0);
    expect($rates->baseExchangeRate)->toBe(1.0);
});

test('it falls back to the last rate published before the document date', function () {
    [$company, $resolver] = documentRatesScenario();

    /** El emisor no publica el fin de semana: el documento del 18 usa la del 16. */
    expect($resolver->forDocument($company->id, 'EUR', '2026-08-18')->exchangeRate)->toBe(40.0);
});

test('it never uses a rate loaded after the document date', function () {
    [$company, $resolver] = documentRatesScenario();

    $resolver->forDocument($company->id, 'EUR', '2026-08-15');
})->throws(ExchangeRateNotFoundException::class);

test('the manual rate wins when the company allows correcting it', function () {
    [$company, $resolver] = documentRatesScenario();

    $rates = $resolver->forDocument($company->id, 'EUR', '2026-08-16', '41.75');

    expect($rates->exchangeRate)->toBe(41.75);
    /** La de la empresa no se corrige a mano: sigue saliendo del catálogo. */
    expect($rates->baseExchangeRate)->toBe(36.5);
});

test('the manual rate is ignored when the company forbids it', function () {
    [$company, $resolver] = documentRatesScenario(['allows_rate_override' => 'no']);

    $rates = $resolver->forDocument($company->id, 'EUR', '2026-08-16', '41.75');

    expect($rates->exchangeRate)->toBe(40.0);
});

test('the legal and manual series are independent', function () {
    [$company, $resolver] = documentRatesScenario(['rate_type' => 'manual']);

    $resolver->forDocument($company->id, 'EUR', '2026-08-16');
})->throws(ExchangeRateNotFoundException::class);

test('a listed price is reexpressed in the currency of the document', function () {
    [$company, $resolver] = documentRatesScenario();

    $rates = $resolver->forDocument($company->id, 'USD', '2026-08-16');

    /** 100 EUR × 40,00 = 4.000 Bs; 4.000 ÷ 36,50 = 109,5890… USD. */
    expect($resolver->priceInDocumentCurrency($rates, $company->id, '2026-08-16', 100.0, 'EUR'))
        ->toBeGreaterThan(109.5890)
        ->toBeLessThan(109.5891);
});

test('a listed price in the currency of the document is not converted', function () {
    [$company, $resolver] = documentRatesScenario();

    $rates = $resolver->forDocument($company->id, 'USD', '2026-08-16');

    expect($resolver->priceInDocumentCurrency($rates, $company->id, '2026-08-16', 100.0, 'USD'))
        ->toBe(100.0);
});

/**
 * Si el usuario corrigió la tasa del documento, el precio convertido tiene que
 * salir de esa corrección: dentro de un documento solo manda una tasa.
 */
test('a listed price is crossed against the rate the document froze', function () {
    [$company, $resolver] = documentRatesScenario();

    $rates = $resolver->forDocument($company->id, 'USD', '2026-08-16', '40');

    expect($resolver->priceInDocumentCurrency($rates, $company->id, '2026-08-16', 100.0, 'EUR'))
        ->toBe(100.0);
});
