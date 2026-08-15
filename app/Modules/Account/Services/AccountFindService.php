<?php

declare(strict_types=1);

namespace App\Modules\Account\Services;

use App\Modules\Account\Exceptions\AccountNotFoundException;
use App\Modules\Account\Models\Account;
use App\Modules\Account\Repositories\Contracts\AccountRepositoryInterface;

class AccountFindService
{
    public function __construct(
        private readonly AccountRepositoryInterface $repository,
    ) {}

    public function execute(string $id, ?string $companyId = null): Account
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new AccountNotFoundException;
        }

        return $model;
    }
}
