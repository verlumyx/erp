<?php

declare(strict_types=1);

namespace App\Modules\Refund\Services;

use App\Modules\Refund\Commands\ResolveRefundCommand;
use App\Modules\Refund\Exceptions\RefundAlreadyResolvedException;
use App\Modules\Refund\Models\Refund;
use App\Modules\Refund\Repositories\Contracts\RefundRepositoryInterface;

class RefundRejectService
{
    public function __construct(
        private readonly RefundRepositoryInterface $repository,
    ) {}

    public function execute(ResolveRefundCommand $command): Refund
    {
        $model = $this->repository->findOrFail($command->refundId, $command->companyId);

        if (! $model->isPending()) {
            throw new RefundAlreadyResolvedException;
        }

        $this->repository->reject($model, $command);

        return $this->repository->findOrFail($command->refundId, $command->companyId);
    }
}
