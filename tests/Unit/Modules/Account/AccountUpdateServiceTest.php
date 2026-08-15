<?php

declare(strict_types=1);

use App\Modules\Account\Commands\UpdateAccountCommand;
use App\Modules\Account\Models\Account;
use App\Modules\Account\Models\Profile;
use App\Modules\Account\Repositories\Contracts\AccountRepositoryInterface;
use App\Modules\Account\Services\AccountUpdateService;
use Illuminate\Validation\ValidationException;

uses(Tests\TestCase::class);

function accountWithProfile(string $status): Account
{
    $account = new Account(['id' => 'account-uuid']);
    $account->setRelation('profiles', collect([
        new Profile(['number' => 1, 'status' => $status]),
    ]));

    return $account;
}

test('it updates the account and returns the refreshed model', function () {
    $command = new UpdateAccountCommand(
        email: 'new@test.com',
        cost: 10.0,
        fechaCompra: '2026-06-13',
        proximaRenovacion: '2026-07-13',
        status: 'active',
        profiles: [['number' => 1, 'pin' => '1234', 'status' => 'occupied', 'notes' => null]],
    );

    $repository = Mockery::mock(AccountRepositoryInterface::class);
    $repository->expects('findById')->with('account-uuid', 'company-uuid')->andReturn(accountWithProfile('available'));
    $repository->expects('update');
    $repository->expects('findOrFail')->with('account-uuid', 'company-uuid')->andReturn(new Account(['id' => 'account-uuid']));

    $service = new AccountUpdateService($repository);

    expect($service->execute('account-uuid', $command, 'company-uuid'))->toBeInstanceOf(Account::class);
});

test('it rejects an invalid profile status transition without updating', function () {
    // 'cancelled' no es un status de profile válido => transición no permitida.
    $command = new UpdateAccountCommand(
        email: 'new@test.com',
        cost: 10.0,
        fechaCompra: '2026-06-13',
        proximaRenovacion: '2026-07-13',
        status: 'active',
        profiles: [['number' => 1, 'pin' => null, 'status' => 'cancelled', 'notes' => null]],
    );

    $repository = Mockery::mock(AccountRepositoryInterface::class);
    $repository->expects('findById')->andReturn(accountWithProfile('available'));
    $repository->shouldNotReceive('update');

    $service = new AccountUpdateService($repository);

    $service->execute('account-uuid', $command, 'company-uuid');
})->throws(ValidationException::class);
