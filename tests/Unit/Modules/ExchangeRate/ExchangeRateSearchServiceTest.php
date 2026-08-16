<?php

declare(strict_types=1);

use App\Modules\ExchangeRate\Commands\SearchExchangeRateCommand;
use App\Modules\ExchangeRate\Models\ExchangeRate;
use App\Modules\ExchangeRate\Repositories\Contracts\ExchangeRateRepositoryInterface;
use App\Modules\ExchangeRate\Services\ExchangeRateSearchService;

uses(Tests\TestCase::class);

test('it delegates the exchange rate search to the repository', function () {
    $command = new SearchExchangeRateCommand(filters: ['currency' => 'USD'], limit: 10, offset: 0);
    $expected = ['data' => [new ExchangeRate(['currency' => 'USD'])], 'total' => 1];

    $repository = Mockery::mock(ExchangeRateRepositoryInterface::class);
    $repository->expects('search')->with($command)->andReturn($expected);

    $service = new ExchangeRateSearchService($repository);

    expect($service->execute($command))->toBe($expected);
});
