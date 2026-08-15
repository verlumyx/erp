<?php

declare(strict_types=1);

namespace App\Modules\Account\Services;

use App\Modules\Account\Commands\CreateAccountCommand;
use App\Modules\Account\Models\Account;
use App\Modules\Account\Repositories\Contracts\AccountRepositoryInterface;

class AccountCreateService
{
    public function __construct(
        private readonly AccountRepositoryInterface $repository,
    ) {}

    public function execute(CreateAccountCommand $command): Account
    {
        $this->repository->create($command);

        return $this->repository->findOrFail($command->id, $command->companyId);
    }
}
