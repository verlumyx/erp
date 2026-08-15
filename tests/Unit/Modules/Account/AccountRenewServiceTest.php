<?php

declare(strict_types=1);

use App\Modules\Account\Commands\RenewAccountCommand;
use App\Modules\Account\Models\Account;
use App\Modules\Account\Repositories\Contracts\AccountRepositoryInterface;
use App\Modules\Account\Services\AccountRenewService;

uses(Tests\TestCase::class);

test('it renews the account through the repository and returns the refreshed model', function () {
    $command = new RenewAccountCommand(
        id: 'renewal-uuid',
        accountId: 'account-uuid',
        companyId: 'company-uuid',
        amount: 12.50,
        nextRenewal: '2026-08-01',
        createdBy: 'user-uuid',
        notes: 'Pago mensual',
    );

    $account = new Account(['id' => 'account-uuid']);

    $repository = Mockery::mock(AccountRepositoryInterface::class);
    $repository->expects('findOrFail')->with('account-uuid', 'company-uuid')->twice()->andReturn($account);
    $repository->expects('renew')->with($account, $command);

    $service = new AccountRenewService($repository);

    expect($service->execute($command))->toBeInstanceOf(Account::class);
});
