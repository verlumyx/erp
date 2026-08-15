<?php

declare(strict_types=1);

use App\Modules\Service\Commands\CreateServiceCommand;
use App\Modules\Service\Repositories\Contracts\ServiceRepositoryInterface;
use App\Modules\Service\Services\SeedCompanyServicesService;

uses(Tests\TestCase::class);

test('it creates one service per configured platform for the company', function () {
    $defaults = config('streaming.default_services');

    $repository = Mockery::mock(ServiceRepositoryInterface::class);
    $repository->shouldReceive('create')
        ->times(count($defaults))
        ->with(Mockery::type(CreateServiceCommand::class));

    $service = new SeedCompanyServicesService($repository);
    $service->execute('company-uuid');
});

test('each created command carries the company id, name, profiles and logo url', function () {
    $first = config('streaming.default_services')[0];

    $captured = [];
    $repository = Mockery::mock(ServiceRepositoryInterface::class);
    $repository->shouldReceive('create')->andReturnUsing(function (CreateServiceCommand $command) use (&$captured): void {
        $captured[] = $command;
    });

    (new SeedCompanyServicesService($repository))->execute('company-uuid');

    expect($captured[0]->companyId)->toBe('company-uuid');
    expect($captured[0]->name)->toBe($first['name']);
    expect($captured[0]->maxProfiles)->toBe($first['max_profiles']);
    expect($captured[0]->logoUrl)->toBe('/images/streaming/'.$first['slug'].'.svg');
});
