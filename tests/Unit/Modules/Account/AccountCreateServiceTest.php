<?php

declare(strict_types=1);

use App\Modules\Account\Commands\CreateAccountCommand;
use App\Modules\Account\Models\Account;
use App\Modules\Account\Repositories\Contracts\AccountRepositoryInterface;
use App\Modules\Account\Services\AccountCreateService;

uses(Tests\TestCase::class);

test('it creates a account and returns the persisted model', function () {
    $command = new CreateAccountCommand(
        id: 'a1b2c3d4-e5f6-7890-ab12-cd34ef567890',
        companyId: 'company-uuid',
        serviceId: 'service-uuid',
        email: 'x@test.com',
        password: 'secret',
        cost: 12.5,
        fechaCompra: '2026-06-13',
        proximaRenovacion: '2026-07-13',
    );

    $repository = Mockery::mock(AccountRepositoryInterface::class);
    $repository->expects('create')->with($command);
    $repository->expects('findOrFail')
        ->with($command->id, $command->companyId)
        ->andReturn(new Account(['id' => $command->id, 'email' => 'x@test.com']));

    $service = new AccountCreateService($repository);
    $result = $service->execute($command);

    expect($result)->toBeInstanceOf(Account::class);
    expect($result->email)->toBe('x@test.com');
});
