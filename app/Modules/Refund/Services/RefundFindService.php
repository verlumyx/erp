<?php

declare(strict_types=1);

namespace App\Modules\Refund\Services;

use App\Modules\Refund\Exceptions\RefundNotFoundException;
use App\Modules\Refund\Models\Refund;
use App\Modules\Refund\Repositories\Contracts\RefundRepositoryInterface;

class RefundFindService
{
    public function __construct(
        private readonly RefundRepositoryInterface $repository,
    ) {}

    public function execute(string $id, ?string $companyId = null): Refund
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new RefundNotFoundException;
        }

        return $model;
    }
}
