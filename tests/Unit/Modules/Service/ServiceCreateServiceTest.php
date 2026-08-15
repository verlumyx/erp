<?php

declare(strict_types=1);

use App\Modules\Service\Commands\CreateServiceCommand;
use App\Modules\Service\Models\Service;
use App\Modules\Service\Repositories\Contracts\ServiceRepositoryInterface;
use App\Modules\Service\Services\ServiceCreateService;

uses(Tests\TestCase::class);

test('it creates a service and returns the persisted model', function () {
    $command = new CreateServiceCommand(
        id: 'a1b2c3d4-e5f6-7890-ab12-cd34ef567890',
        companyId: 'company-uuid',
        name: 'Netflix',
        maxProfiles: 5,
        logoUrl: 'https://logo.test/netflix.png',
    );

    $repository = Mockery::mock(ServiceRepositoryInterface::class);
    $repository->expects('create')->with($command);
    $repository->expects('findOrFail')
        ->with($command->id)
        ->andReturn(new Service(['id' => $command->id, 'name' => 'Netflix']));

    $service = new ServiceCreateService($repository);
    $result = $service->execute($command);

    expect($result)->toBeInstanceOf(Service::class);
    expect($result->name)->toBe('Netflix');
});
