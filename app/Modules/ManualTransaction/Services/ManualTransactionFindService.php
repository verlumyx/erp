<?php

declare(strict_types=1);

namespace App\Modules\ManualTransaction\Services;

use App\Modules\ManualTransaction\Exceptions\ManualTransactionNotFoundException;
use App\Modules\ManualTransaction\Models\ManualTransaction;
use App\Modules\ManualTransaction\Repositories\Contracts\ManualTransactionRepositoryInterface;

class ManualTransactionFindService
{
    public function __construct(
        private readonly ManualTransactionRepositoryInterface $repository,
    ) {}

    public function execute(string $id, ?string $companyId = null): ManualTransaction
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new ManualTransactionNotFoundException;
        }

        return $model;
    }
}
