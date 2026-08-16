<?php

declare(strict_types=1);

namespace App\Modules\PurchaseOrder\Services;

use App\Modules\PurchaseOrder\Commands\UpdatePurchaseOrderCommand;
use App\Modules\PurchaseOrder\Exceptions\PurchaseOrderNotFoundException;
use App\Modules\PurchaseOrder\Models\PurchaseOrder;
use App\Modules\PurchaseOrder\Repositories\Contracts\PurchaseOrderRepositoryInterface;

class PurchaseOrderUpdateService
{
    public function __construct(
        private readonly PurchaseOrderRepositoryInterface $repository,
    ) {}

    public function execute(string $id, UpdatePurchaseOrderCommand $command, ?string $companyId = null): PurchaseOrder
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new PurchaseOrderNotFoundException;
        }

        $this->repository->update($model, $command);

        return $this->repository->findOrFail($id, $companyId);
    }
}
