<?php

declare(strict_types=1);

use App\Modules\Configuration\Commands\CreateConfigurationCommand;
use App\Modules\Configuration\Models\Configuration;
use App\Modules\Configuration\Repositories\Contracts\ConfigurationRepositoryInterface;
use App\Modules\Configuration\Services\ConfigurationFindService;

uses(Tests\TestCase::class);

test('it returns the configuration of the company', function () {
    $repository = Mockery::mock(ConfigurationRepositoryInterface::class);
    $repository->expects('findByCompany')
        ->with('company-uuid')
        ->andReturn(new Configuration(['base_currency' => 'EUR']));

    $service = new ConfigurationFindService($repository);

    expect($service->execute('company-uuid')->base_currency)->toBe('EUR');
});

/**
 * Las empresas creadas antes de este módulo no tienen fila: la estrenan al
 * primer acceso en vez de romper la pantalla.
 */
test('a company without configuration gets the defaults', function () {
    $repository = Mockery::mock(ConfigurationRepositoryInterface::class);

    $repository->expects('findByCompany')
        ->with('company-uuid')
        ->andReturnNull();

    $repository->expects('findOrFailByCompany')
        ->with('company-uuid')
        ->andReturn(new Configuration(['base_currency' => 'USD']));

    $repository->expects('create')
        ->withArgs(fn (CreateConfigurationCommand $command): bool => $command->companyId === 'company-uuid'
            && $command->baseCurrency === 'USD'
            && $command->secondaryCurrency === 'VES');

    $service = new ConfigurationFindService($repository);

    expect($service->execute('company-uuid')->base_currency)->toBe('USD');
});
