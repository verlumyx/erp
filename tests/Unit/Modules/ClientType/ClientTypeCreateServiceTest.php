<?php

declare(strict_types=1);

use App\Modules\ClientType\Commands\CreateClientTypeCommand;
use App\Modules\ClientType\Models\ClientType;
use App\Modules\ClientType\Repositories\Contracts\ClientTypeRepositoryInterface;
use App\Modules\ClientType\Services\ClientTypeCreateService;

uses(Tests\TestCase::class);

test('it creates a client type and returns the persisted model', function () {
    $command = new CreateClientTypeCommand(
        id: 'a1b2c3d4-e5f6-7890-ab12-cd34ef567890',
        companyId: 'company-uuid',
        name: 'Mayorista',
        createdBy: 'user-uuid',
        description: 'Clientes de compra por volumen',
    );

    $repository = Mockery::mock(ClientTypeRepositoryInterface::class);
    $repository->expects('create')->with($command);
    $repository->expects('findOrFail')
        ->with($command->id)
        ->andReturn(new ClientType(['id' => $command->id, 'name' => 'Mayorista']));

    $service = new ClientTypeCreateService($repository);
    $result = $service->execute($command);

    expect($result)->toBeInstanceOf(ClientType::class);
    expect($result->name)->toBe('Mayorista');
});
