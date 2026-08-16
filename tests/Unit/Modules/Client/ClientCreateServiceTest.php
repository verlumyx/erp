<?php

declare(strict_types=1);

use App\Modules\Client\Commands\CreateClientCommand;
use App\Modules\Client\Models\Client;
use App\Modules\Client\Repositories\Contracts\ClientRepositoryInterface;
use App\Modules\Client\Services\ClientCreateService;

uses(Tests\TestCase::class);

test('it creates a client and returns the persisted model', function () {
    $command = new CreateClientCommand(
        id: 'a1b2c3d4-e5f6-7890-ab12-cd34ef567890',
        companyId: 'company-uuid',
        name: 'Acme',
        documentType: 'J',
        documentNumber: '123456789',
        createdBy: 'user-uuid',
        email: 'acme@test.com',
    );

    $repository = Mockery::mock(ClientRepositoryInterface::class);
    $repository->expects('create')->with($command);
    $repository->expects('findOrFail')
        ->with($command->id)
        ->andReturn(new Client(['id' => $command->id, 'name' => 'Acme']));

    $service = new ClientCreateService($repository);
    $result = $service->execute($command);

    expect($result)->toBeInstanceOf(Client::class);
    expect($result->name)->toBe('Acme');
});
