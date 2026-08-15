<?php

declare(strict_types=1);

namespace App\Modules\Refund\Services;

use App\Modules\Refund\Commands\CreateRefundCommand;
use App\Modules\Refund\Models\Refund;
use App\Modules\Refund\Repositories\Contracts\RefundRepositoryInterface;

class RefundCreateService
{
    public function __construct(
        private readonly RefundRepositoryInterface $repository,
    ) {}

    public function execute(CreateRefundCommand $command): Refund
    {
        $this->repository->create($command);

        return $this->repository->findOrFail($command->id, $command->companyId);
    }
}
