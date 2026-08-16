<?php

declare(strict_types=1);

use App\Modules\Warehouse\Commands\CreateWarehouseCommand;
use App\Modules\Warehouse\Models\Warehouse;
use App\Modules\Warehouse\Repositories\Contracts\WarehouseRepositoryInterface;
use App\Modules\Warehouse\Services\WarehouseCreateService;
use App\Modules\WarehouseLocation\Commands\CreateWarehouseLocationCommand;
use App\Modules\WarehouseLocation\Repositories\Contracts\WarehouseLocationRepositoryInterface;

uses(Tests\TestCase::class);

test('it creates a warehouse and returns the persisted model', function () {
    $command = new CreateWarehouseCommand(
        id: 'a1b2c3d4-e5f6-7890-ab12-cd34ef567890',
        companyId: 'company-uuid',
        name: 'Bodega Central',
        createdBy: 'user-uuid',
        type: 'main',
    );

    $repository = Mockery::mock(WarehouseRepositoryInterface::class);
    $repository->expects('create')->with($command);
    $repository->expects('findOrFail')
        ->with($command->id)
        ->andReturn(new Warehouse(['id' => $command->id, 'name' => 'Bodega Central']));

    $locationRepository = Mockery::mock(WarehouseLocationRepositoryInterface::class);
    $locationRepository->expects('create')->withArgs(
        fn (CreateWarehouseLocationCommand $location): bool => $location->warehouseId === $command->id
            && $location->name === 'Principal'
            && $location->locationCode === 'PRINCIPAL'
            && $location->type === 'zone'
            && $location->isDefault === 'yes'
            && $location->companyId === $command->companyId
    );

    $service = new WarehouseCreateService($repository, $locationRepository);
    $result = $service->execute($command);

    expect($result)->toBeInstanceOf(Warehouse::class);
    expect($result->name)->toBe('Bodega Central');
});
