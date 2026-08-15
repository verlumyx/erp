<?php

declare(strict_types=1);

use App\Modules\Account\Exceptions\AccountNotFoundException;
use App\Modules\Account\Models\Account;
use App\Modules\Account\Repositories\Contracts\AccountRepositoryInterface;
use App\Modules\Account\Services\AccountFindService;

uses(Tests\TestCase::class);

test('it returns the account when found', function () {
    $repository = Mockery::mock(AccountRepositoryInterface::class);
    $repository->expects('findById')
        ->with('account-uuid', 'company-uuid')
        ->andReturn(new Account(['id' => 'account-uuid']));

    $service = new AccountFindService($repository);

    expect($service->execute('account-uuid', 'company-uuid'))->toBeInstanceOf(Account::class);
});

test('it throws when the account is not found', function () {
    $repository = Mockery::mock(AccountRepositoryInterface::class);
    $repository->expects('findById')->andReturn(null);

    $service = new AccountFindService($repository);

    $service->execute('missing-uuid');
})->throws(AccountNotFoundException::class);
