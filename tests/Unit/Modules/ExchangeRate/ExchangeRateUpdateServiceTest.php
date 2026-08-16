<?php

declare(strict_types=1);

use App\Modules\ExchangeRate\Commands\UpdateExchangeRateCommand;
use App\Modules\ExchangeRate\Exceptions\ExchangeRateNotFoundException;
use App\Modules\ExchangeRate\Models\ExchangeRate;
use App\Modules\ExchangeRate\Repositories\Contracts\ExchangeRateRepositoryInterface;
use App\Modules\ExchangeRate\Services\ExchangeRateUpdateService;

uses(Tests\TestCase::class);

test('it updates the exchange rate and returns the refreshed model', function () {
    $command = new UpdateExchangeRateCommand(
        currency: 'USD',
        rateDate: '2026-08-15',
        rate: '37.00000000',
        type: 'legal',
    );
    $model = new ExchangeRate(['currency' => 'USD', 'rate' => '36.00000000']);

    $repository = Mockery::mock(ExchangeRateRepositoryInterface::class);
    $repository->expects('findById')->with('rate-uuid', null)->andReturn($model);
    $repository->expects('update')->with($model, $command);
    $repository->expects('findOrFail')
        ->with('rate-uuid', null)
        ->andReturn(new ExchangeRate(['currency' => 'USD', 'rate' => '37.00000000']));

    $service = new ExchangeRateUpdateService($repository);

    expect($service->execute('rate-uuid', $command)->rate)->toBe('37.00000000');
});

test('it throws when updating a missing exchange rate', function () {
    $command = new UpdateExchangeRateCommand(
        currency: 'USD',
        rateDate: '2026-08-15',
        rate: '37.00000000',
        type: 'legal',
    );

    $repository = Mockery::mock(ExchangeRateRepositoryInterface::class);
    $repository->expects('findById')->with('missing-uuid', null)->andReturnNull();

    $service = new ExchangeRateUpdateService($repository);

    $service->execute('missing-uuid', $command);
})->throws(ExchangeRateNotFoundException::class);
