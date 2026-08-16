<?php

declare(strict_types=1);

namespace App\Modules\PurchaseOrder\Services;

use App\Modules\PurchaseOrder\Exceptions\PurchaseOrderNotFoundException;
use App\Modules\PurchaseOrder\Models\PurchaseOrder;
use App\Modules\PurchaseOrder\Repositories\Contracts\PurchaseOrderRepositoryInterface;

class PurchaseOrderFindService
{
    public function __construct(
        private readonly PurchaseOrderRepositoryInterface $repository,
    ) {}

    public function execute(string $id, ?string $companyId = null): PurchaseOrder
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new PurchaseOrderNotFoundException;
        }

        return $model;
    }
}
