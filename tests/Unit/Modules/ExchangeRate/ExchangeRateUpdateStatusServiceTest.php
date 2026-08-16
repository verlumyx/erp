<?php

declare(strict_types=1);

use App\Modules\ExchangeRate\Commands\UpdateStatusExchangeRateCommand;
use App\Modules\ExchangeRate\Exceptions\ExchangeRateNotFoundException;
use App\Modules\ExchangeRate\Models\ExchangeRate;
use App\Modules\ExchangeRate\Repositories\Contracts\ExchangeRateRepositoryInterface;
use App\Modules\ExchangeRate\Services\ExchangeRateUpdateStatusService;

uses(Tests\TestCase::class);

test('it updates the exchange rate status and returns the refreshed model', function () {
    $command = new UpdateStatusExchangeRateCommand(status: 'inactive');
    $model = new ExchangeRate(['status' => 'active']);

    $repository = Mockery::mock(ExchangeRateRepositoryInterface::class);
    $repository->expects('findById')->with('rate-uuid', null)->andReturn($model);
    $repository->expects('updateStatus')->with($model, $command);
    $repository->expects('findOrFail')
        ->with('rate-uuid', null)
        ->andReturn(new ExchangeRate(['status' => 'inactive']));

    $service = new ExchangeRateUpdateStatusService($repository);

    expect($service->execute('rate-uuid', $command)->status)->toBe('inactive');
});

test('it throws when changing the status of a missing exchange rate', function () {
    $command = new UpdateStatusExchangeRateCommand(status: 'inactive');

    $repository = Mockery::mock(ExchangeRateRepositoryInterface::class);
    $repository->expects('findById')->with('missing-uuid', null)->andReturnNull();

    $service = new ExchangeRateUpdateStatusService($repository);

    $service->execute('missing-uuid', $command);
})->throws(ExchangeRateNotFoundException::class);
