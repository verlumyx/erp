<?php

declare(strict_types=1);

use App\Modules\ExchangeRate\Exceptions\ExchangeRateNotFoundException;
use App\Modules\ExchangeRate\Models\ExchangeRate;
use App\Modules\ExchangeRate\Repositories\Contracts\ExchangeRateRepositoryInterface;
use App\Modules\ExchangeRate\Services\ExchangeRateFindService;

uses(Tests\TestCase::class);

test('it returns the exchange rate when it exists', function () {
    $repository = Mockery::mock(ExchangeRateRepositoryInterface::class);
    $repository->expects('findById')
        ->with('rate-uuid', null)
        ->andReturn(new ExchangeRate(['currency' => 'EUR']));

    $service = new ExchangeRateFindService($repository);

    expect($service->execute('rate-uuid')->currency)->toBe('EUR');
});

test('it throws when the exchange rate does not exist', function () {
    $repository = Mockery::mock(ExchangeRateRepositoryInterface::class);
    $repository->expects('findById')->with('missing-uuid', null)->andReturnNull();

    $service = new ExchangeRateFindService($repository);

    $service->execute('missing-uuid');
})->throws(ExchangeRateNotFoundException::class);
