<?php

declare(strict_types=1);

namespace App\Modules\PurchaseReturn\Services;

use App\Modules\PurchaseReturn\Exceptions\PurchaseReturnNotFoundException;
use App\Modules\PurchaseReturn\Models\PurchaseReturn;
use App\Modules\PurchaseReturn\Repositories\Contracts\PurchaseReturnRepositoryInterface;

class PurchaseReturnFindService
{
    public function __construct(
        private readonly PurchaseReturnRepositoryInterface $repository,
    ) {}

    public function execute(string $id, ?string $companyId = null): PurchaseReturn
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new PurchaseReturnNotFoundException;
        }

        return $model;
    }
}
