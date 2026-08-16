<?php

declare(strict_types=1);

use App\Modules\Supplier\Commands\CreateSupplierCommand;
use App\Modules\Supplier\Commands\SupplierContactData;
use App\Modules\Supplier\Models\Supplier;
use App\Modules\Supplier\Repositories\Contracts\SupplierRepositoryInterface;
use App\Modules\Supplier\Services\SupplierCreateService;

uses(Tests\TestCase::class);

test('it creates a supplier and returns the persisted model', function () {
    $command = new CreateSupplierCommand(
        id: 'a1b2c3d4-e5f6-7890-ab12-cd34ef567890',
        companyId: 'company-uuid',
        name: 'Distribuidora Andina',
        documentType: 'J',
        documentNumber: '123456789',
        createdBy: 'user-uuid',
        contacts: [new SupplierContactData(
            id: null,
            name: 'María Pérez',
            isPrimary: 'yes',
        )],
    );

    $repository = Mockery::mock(SupplierRepositoryInterface::class);
    $repository->expects('create')->with($command);
    $repository->expects('findOrFail')
        ->with($command->id)
        ->andReturn(new Supplier(['id' => $command->id, 'name' => 'Distribuidora Andina']));

    $service = new SupplierCreateService($repository);
    $result = $service->execute($command);

    expect($result)->toBeInstanceOf(Supplier::class);
    expect($result->name)->toBe('Distribuidora Andina');
});
