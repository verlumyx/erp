<?php

declare(strict_types=1);

use App\Modules\ExchangeRate\Exceptions\ExchangeRateNotFoundException;
use App\Modules\ExchangeRate\Models\ExchangeRate;
use App\Modules\ExchangeRate\Services\Contracts\ExchangeRateResolverInterface;

/**
 * Qué tasa toma un documento cuando se valora. La regla es "la última cargada
 * con fecha igual o anterior": el emisor legal no publica todos los días.
 */
function resolver(): ExchangeRateResolverInterface
{
    return app(ExchangeRateResolverInterface::class);
}

test('it uses the rate of the document date when it exists', function () {
    [, $company] = createUserWithCompany();

    ExchangeRate::factory()->create([
        'company_id' => $company->id,
        'currency' => 'USD',
        'rate_date' => '2026-08-16',
        'rate' => 36.5,
        'type' => 'legal',
    ]);

    expect(resolver()->rateFor($company->id, 'USD', '2026-08-16'))->toBe(36.5);
});

test('it falls back to the last rate published before the document date', function () {
    [, $company] = createUserWithCompany();

    ExchangeRate::factory()->create([
        'company_id' => $company->id,
        'currency' => 'USD',
        'rate_date' => '2026-08-14',
        'rate' => 36.5,
        'type' => 'legal',
    ]);

    // Sábado: no hay publicación, se valora con la del viernes.
    expect(resolver()->rateFor($company->id, 'USD', '2026-08-15'))->toBe(36.5);
});

test('it never uses a rate published after the document date', function () {
    [, $company] = createUserWithCompany();

    ExchangeRate::factory()->create([
        'company_id' => $company->id,
        'currency' => 'USD',
        'rate_date' => '2026-08-20',
        'rate' => 40.0,
        'type' => 'legal',
    ]);

    expect(fn () => resolver()->rateFor($company->id, 'USD', '2026-08-16'))
        ->toThrow(ExchangeRateNotFoundException::class);
});

test('it takes the most recent rate when several are available', function () {
    [, $company] = createUserWithCompany();

    foreach ([['2026-08-10', 34.0], ['2026-08-14', 36.5], ['2026-08-12', 35.0]] as [$date, $rate]) {
        ExchangeRate::factory()->create([
            'company_id' => $company->id,
            'currency' => 'USD',
            'rate_date' => $date,
            'rate' => $rate,
            'type' => 'legal',
        ]);
    }

    expect(resolver()->rateFor($company->id, 'USD', '2026-08-16'))->toBe(36.5);
});

test('an inactive rate is ignored', function () {
    [, $company] = createUserWithCompany();

    ExchangeRate::factory()->inactive()->create([
        'company_id' => $company->id,
        'currency' => 'USD',
        'rate_date' => '2026-08-16',
        'rate' => 99.0,
        'type' => 'legal',
    ]);

    ExchangeRate::factory()->create([
        'company_id' => $company->id,
        'currency' => 'USD',
        'rate_date' => '2026-08-14',
        'rate' => 36.5,
        'type' => 'legal',
    ]);

    expect(resolver()->rateFor($company->id, 'USD', '2026-08-16'))->toBe(36.5);
});

test('each company is valued with its own rates', function () {
    [, $company] = createUserWithCompany();
    [, $other] = createUserWithCompany();

    ExchangeRate::factory()->create([
        'company_id' => $other->id,
        'currency' => 'USD',
        'rate_date' => '2026-08-16',
        'rate' => 99.0,
        'type' => 'legal',
    ]);

    expect(fn () => resolver()->rateFor($company->id, 'USD', '2026-08-16'))
        ->toThrow(ExchangeRateNotFoundException::class);
});

test('the manual rate does not mix with the legal one', function () {
    [, $company] = createUserWithCompany();

    ExchangeRate::factory()->create([
        'company_id' => $company->id,
        'currency' => 'USD',
        'rate_date' => '2026-08-16',
        'rate' => 36.5,
        'type' => 'legal',
    ]);

    ExchangeRate::factory()->manual()->create([
        'company_id' => $company->id,
        'currency' => 'USD',
        'rate_date' => '2026-08-16',
        'rate' => 45.0,
        'type' => 'manual',
    ]);

    expect(resolver()->rateFor($company->id, 'USD', '2026-08-16', 'manual'))->toBe(45.0)
        ->and(resolver()->rateFor($company->id, 'USD', '2026-08-16', 'legal'))->toBe(36.5);
});
