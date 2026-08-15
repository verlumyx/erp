<?php

declare(strict_types=1);

namespace App\Modules\Refund\Services;

use App\Modules\Refund\Commands\UpdateRefundCommand;
use App\Modules\Refund\Exceptions\RefundAlreadyResolvedException;
use App\Modules\Refund\Exceptions\RefundNotFoundException;
use App\Modules\Refund\Models\Refund;
use App\Modules\Refund\Repositories\Contracts\RefundRepositoryInterface;

class RefundUpdateService
{
    public function __construct(
        private readonly RefundRepositoryInterface $repository,
    ) {}

    public function execute(string $id, UpdateRefundCommand $command, ?string $companyId = null): Refund
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new RefundNotFoundException;
        }

        if (! $model->isPending()) {
            throw new RefundAlreadyResolvedException;
        }

        $this->repository->update($model, $command);

        return $this->repository->findOrFail($id, $companyId);
    }
}
