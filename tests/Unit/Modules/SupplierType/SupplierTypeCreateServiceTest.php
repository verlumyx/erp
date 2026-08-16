<?php

declare(strict_types=1);

use App\Modules\SupplierType\Commands\CreateSupplierTypeCommand;
use App\Modules\SupplierType\Models\SupplierType;
use App\Modules\SupplierType\Repositories\Contracts\SupplierTypeRepositoryInterface;
use App\Modules\SupplierType\Services\SupplierTypeCreateService;

uses(Tests\TestCase::class);

test('it creates a supplier type and returns the persisted model', function () {
    $command = new CreateSupplierTypeCommand(
        id: 'a1b2c3d4-e5f6-7890-ab12-cd34ef567890',
        companyId: 'company-uuid',
        name: 'Nacional',
        createdBy: 'user-uuid',
        description: 'Proveedores del país',
    );

    $repository = Mockery::mock(SupplierTypeRepositoryInterface::class);
    $repository->expects('create')->with($command);
    $repository->expects('findOrFail')
        ->with($command->id)
        ->andReturn(new SupplierType(['id' => $command->id, 'name' => 'Nacional']));

    $service = new SupplierTypeCreateService($repository);
    $result = $service->execute($command);

    expect($result)->toBeInstanceOf(SupplierType::class);
    expect($result->name)->toBe('Nacional');
});
