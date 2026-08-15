<?php

declare(strict_types=1);

use App\Modules\Account\Exceptions\AccountNotFoundException;
use App\Modules\Account\Models\Account;
use App\Modules\Account\Repositories\Contracts\AccountRepositoryInterface;
use App\Modules\Account\Services\AccountCredentialsService;
use Illuminate\Support\Facades\Log;

uses(Tests\TestCase::class);

test('it returns the decrypted credentials and logs the access', function () {
    $account = new Account([
        'id' => 'account-uuid',
        'company_id' => 'company-uuid',
        'code' => 'ACC000001',
        'email' => 'creds@test.com',
        'password_encrypted' => 'plain-pass',
    ]);

    $repository = Mockery::mock(AccountRepositoryInterface::class);
    $repository->expects('findById')->with('account-uuid', 'company-uuid')->andReturn($account);

    Log::shouldReceive('info')->once()->withArgs(
        fn (string $message, array $context = []): bool => $message === 'account.credentials.accessed'
            && $context['user_id'] === 'user-uuid'
            && $context['account_id'] === 'account-uuid'
    );

    $service = new AccountCredentialsService($repository);
    $result = $service->execute('account-uuid', 'user-uuid', 'company-uuid');

    expect($result)->toBe(['email' => 'creds@test.com', 'password' => 'plain-pass']);
});

test('it throws when the account is not found', function () {
    $repository = Mockery::mock(AccountRepositoryInterface::class);
    $repository->expects('findById')->andReturn(null);

    $service = new AccountCredentialsService($repository);

    $service->execute('missing', 'user-uuid');
})->throws(AccountNotFoundException::class);
