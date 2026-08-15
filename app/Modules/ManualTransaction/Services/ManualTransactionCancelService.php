<?php

declare(strict_types=1);

namespace App\Modules\ManualTransaction\Services;

use App\Modules\ManualTransaction\Exceptions\InvalidManualTransactionStatusException;
use App\Modules\ManualTransaction\Models\ManualTransaction;
use App\Modules\ManualTransaction\Repositories\Contracts\ManualTransactionRepositoryInterface;

class ManualTransactionCancelService
{
    public function __construct(
        private readonly ManualTransactionRepositoryInterface $repository,
    ) {}

    public function execute(string $id, ?string $companyId): ManualTransaction
    {
        $model = $this->repository->findOrFail($id, $companyId);

        if (! $model->canBeCancelled()) {
            throw new InvalidManualTransactionStatusException;
        }

        $this->repository->cancel($model);

        return $this->repository->findOrFail($id, $companyId);
    }
}
