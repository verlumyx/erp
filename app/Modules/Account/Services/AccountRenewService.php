<?php

declare(strict_types=1);

namespace App\Modules\Account\Services;

use App\Modules\Account\Commands\RenewAccountCommand;
use App\Modules\Account\Models\Account;
use App\Modules\Account\Repositories\Contracts\AccountRepositoryInterface;

class AccountRenewService
{
    public function __construct(
        private readonly AccountRepositoryInterface $repository,
    ) {}

    public function execute(RenewAccountCommand $command): Account
    {
        $account = $this->repository->findOrFail($command->accountId, $command->companyId);

        $this->repository->renew($account, $command);

        return $this->repository->findOrFail($command->accountId, $command->companyId);
    }
}
