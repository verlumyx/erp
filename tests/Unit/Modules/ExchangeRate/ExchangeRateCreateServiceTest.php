<?php

declare(strict_types=1);

use App\Modules\ExchangeRate\Commands\CreateExchangeRateCommand;
use App\Modules\ExchangeRate\Exceptions\ExchangeRateNotFoundException;
use App\Modules\ExchangeRate\Models\ExchangeRate;
use App\Modules\ExchangeRate\Repositories\Contracts\ExchangeRateRepositoryInterface;
use App\Modules\ExchangeRate\Services\ExchangeRateCreateService;

uses(Tests\TestCase::class);

function createExchangeRateCommand(): CreateExchangeRateCommand
{
    return new CreateExchangeRateCommand(
        id: 'a1b2c3d4-e5f6-7890-ab12-cd34ef567890',
        companyId: 'company-uuid',
        currency: 'USD',
        rateDate: '2026-08-15',
        rate: '36.50000000',
        type: 'legal',
        createdBy: 'user-uuid',
        source: 'Banco Central',
        description: 'Tasa oficial',
    );
}

test('it creates an exchange rate and returns the persisted model', function () {
    $command = createExchangeRateCommand();

    $repository = Mockery::mock(ExchangeRateRepositoryInterface::class);
    $repository->expects('create')->with($command);
    $repository->expects('findByRateKey')
        ->with('company-uuid', 'USD', '2026-08-15', 'legal')
        ->andReturn(new ExchangeRate(['id' => $command->id, 'currency' => 'USD', 'rate' => '36.50000000']));

    $service = new ExchangeRateCreateService($repository);
    $result = $service->execute($command);

    expect($result)->toBeInstanceOf(ExchangeRate::class);
    expect($result->currency)->toBe('USD');
});

test('it throws when the rate cannot be resolved after being persisted', function () {
    $command = createExchangeRateCommand();

    $repository = Mockery::mock(ExchangeRateRepositoryInterface::class);
    $repository->expects('create')->with($command);
    $repository->expects('findByRateKey')->andReturnNull();

    $service = new ExchangeRateCreateService($repository);

    $service->execute($command);
})->throws(ExchangeRateNotFoundException::class);
