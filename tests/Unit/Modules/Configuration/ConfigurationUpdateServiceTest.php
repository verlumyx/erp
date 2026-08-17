<?php

declare(strict_types=1);

use App\Modules\Configuration\Commands\UpdateConfigurationCommand;
use App\Modules\Configuration\Models\Configuration;
use App\Modules\Configuration\Repositories\Contracts\ConfigurationRepositoryInterface;
use App\Modules\Configuration\Services\ConfigurationFindService;
use App\Modules\Configuration\Services\ConfigurationUpdateService;

uses(Tests\TestCase::class);

test('it updates the configuration of the company', function () {
    $existing = new Configuration(['base_currency' => 'USD']);
    $updated = new Configuration(['base_currency' => 'VES']);

    $command = new UpdateConfigurationCommand(
        baseCurrency: 'VES',
        secondaryCurrency: 'VES',
        rateType: 'legal',
        allowsRateOverride: 'no',
        amountDecimals: 2,
        priceDecimals: 6,
    );

    $repository = Mockery::mock(ConfigurationRepositoryInterface::class);
    $repository->expects('findByCompany')->with('company-uuid')->andReturn($existing);
    $repository->expects('update')->with($existing, $command);
    $repository->expects('findOrFailByCompany')->with('company-uuid')->andReturn($updated);

    $service = new ConfigurationUpdateService(
        $repository,
        new ConfigurationFindService($repository),
    );

    expect($service->execute('company-uuid', $command)->base_currency)->toBe('VES');
});
