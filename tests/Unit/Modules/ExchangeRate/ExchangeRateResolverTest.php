<?php

declare(strict_types=1);

use App\Modules\ExchangeRate\Exceptions\ExchangeRateNotFoundException;
use App\Modules\ExchangeRate\Models\ExchangeRate;
use App\Modules\ExchangeRate\Repositories\Contracts\ExchangeRateRepositoryInterface;
use App\Modules\ExchangeRate\Services\ExchangeRateResolver;

uses(Tests\TestCase::class);

/**
 * La tasa siempre significa lo mismo: cuántos bolívares vale 1 unidad de la
 * moneda. Todo lo demás del sistema se apoya en esa definición.
 */
test('the bolivar is always worth one and never hits the database', function () {
    $repository = Mockery::mock(ExchangeRateRepositoryInterface::class);
    $repository->shouldNotReceive('findLatestUpTo');

    $resolver = new ExchangeRateResolver($repository);

    expect($resolver->rateFor('company-uuid', 'VES', '2026-08-16'))->toBe(1.0);
});

test('it returns how many bolivares one unit of the currency is worth', function () {
    $repository = Mockery::mock(ExchangeRateRepositoryInterface::class);
    $repository->expects('findLatestUpTo')
        ->with('company-uuid', 'USD', '2026-08-16', 'legal')
        ->andReturn(new ExchangeRate(['rate' => '36.50000000']));

    $resolver = new ExchangeRateResolver($repository);

    expect($resolver->rateFor('company-uuid', 'USD', '2026-08-16'))->toBe(36.5);
});

test('it resolves the same rate only once per request', function () {
    $repository = Mockery::mock(ExchangeRateRepositoryInterface::class);
    $repository->expects('findLatestUpTo')
        ->once()
        ->andReturn(new ExchangeRate(['rate' => '36.50000000']));

    $resolver = new ExchangeRateResolver($repository);

    $resolver->rateFor('company-uuid', 'USD', '2026-08-16');
    $resolver->rateFor('company-uuid', 'USD', '2026-08-16');

    expect($resolver->rateFor('company-uuid', 'USD', '2026-08-16'))->toBe(36.5);
});

test('it fails with an actionable message when no rate applies', function () {
    $repository = Mockery::mock(ExchangeRateRepositoryInterface::class);
    $repository->expects('findLatestUpTo')->andReturnNull();

    $resolver = new ExchangeRateResolver($repository);

    expect(fn () => $resolver->rateFor('company-uuid', 'USD', '2026-08-16'))
        ->toThrow(
            ExchangeRateNotFoundException::class,
            'No hay tasa de cambio cargada para USD al 2026-08-16.',
        );
});

test('it converts between two foreign currencies crossing through bolivares', function () {
    $repository = Mockery::mock(ExchangeRateRepositoryInterface::class);
    $repository->expects('findLatestUpTo')
        ->with('company-uuid', 'EUR', '2026-08-16', 'legal')
        ->andReturn(new ExchangeRate(['rate' => '40.00000000']));
    $repository->expects('findLatestUpTo')
        ->with('company-uuid', 'USD', '2026-08-16', 'legal')
        ->andReturn(new ExchangeRate(['rate' => '32.00000000']));

    $resolver = new ExchangeRateResolver($repository);

    // 100 EUR = 4.000 Bs = 125 USD
    expect($resolver->convert(100, 'EUR', 'USD', 'company-uuid', '2026-08-16'))->toBe(125.0);
});

test('converting a currency into itself does not need any rate', function () {
    $repository = Mockery::mock(ExchangeRateRepositoryInterface::class);
    $repository->shouldNotReceive('findLatestUpTo');

    $resolver = new ExchangeRateResolver($repository);

    expect($resolver->convert(100, 'USD', 'USD', 'company-uuid', '2026-08-16'))->toBe(100.0);
});

test('it converts a foreign amount into bolivares', function () {
    $repository = Mockery::mock(ExchangeRateRepositoryInterface::class);
    $repository->expects('findLatestUpTo')
        ->with('company-uuid', 'USD', '2026-08-16', 'legal')
        ->andReturn(new ExchangeRate(['rate' => '36.50000000']));

    $resolver = new ExchangeRateResolver($repository);

    expect($resolver->convert(100, 'USD', 'VES', 'company-uuid', '2026-08-16'))->toBe(3650.0);
});
