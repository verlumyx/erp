<?php

declare(strict_types=1);

use App\Modules\Account\Commands\SearchAccountCommand;
use App\Modules\Account\Models\Account;
use App\Modules\Account\Repositories\Contracts\AccountRepositoryInterface;
use App\Modules\Account\Services\AccountSearchService;

uses(Tests\TestCase::class);

test('it delegates the search to the repository', function () {
    $command = new SearchAccountCommand(filters: ['status' => 'active'], companyId: 'company-uuid');

    $expected = ['data' => [new Account(['id' => 'account-uuid'])], 'total' => 1];

    $repository = Mockery::mock(AccountRepositoryInterface::class);
    $repository->expects('search')->with($command)->andReturn($expected);

    $service = new AccountSearchService($repository);

    expect($service->execute($command))->toBe($expected);
});
