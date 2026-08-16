<?php

declare(strict_types=1);

use App\Modules\WarehouseLocation\Commands\CreateWarehouseLocationCommand;
use App\Modules\WarehouseLocation\Models\WarehouseLocation;
use App\Modules\WarehouseLocation\Repositories\Contracts\WarehouseLocationRepositoryInterface;
use App\Modules\WarehouseLocation\Services\WarehouseLocationCreateService;

uses(Tests\TestCase::class);

test('it creates a location and returns the persisted model', function () {
    $command = new CreateWarehouseLocationCommand(
        id: 'a1b2c3d4-e5f6-7890-ab12-cd34ef567890',
        companyId: 'company-uuid',
        warehouseId: 'warehouse-uuid',
        name: 'Estante A1',
        locationCode: 'A-01-03',
        createdBy: 'user-uuid',
    );

    $repository = Mockery::mock(WarehouseLocationRepositoryInterface::class);
    $repository->expects('create')->with($command);
    $repository->expects('findOrFail')
        ->with($command->id)
        ->andReturn(new WarehouseLocation(['id' => $command->id, 'name' => 'Estante A1']));

    $result = (new WarehouseLocationCreateService($repository))->execute($command);

    expect($result)->toBeInstanceOf(WarehouseLocation::class);
    expect($result->name)->toBe('Estante A1');
});
