<?php

declare(strict_types=1);

namespace App\Modules\PurchaseOrder\Services;

use App\Modules\PurchaseOrder\Commands\CreatePurchaseOrderCommand;
use App\Modules\PurchaseOrder\Models\PurchaseOrder;
use App\Modules\PurchaseOrder\Repositories\Contracts\PurchaseOrderRepositoryInterface;

class PurchaseOrderCreateService
{
    public function __construct(
        private readonly PurchaseOrderRepositoryInterface $repository,
    ) {}

    public function execute(CreatePurchaseOrderCommand $command): PurchaseOrder
    {
        $this->repository->create($command);

        return $this->repository->findOrFail($command->id);
    }
}
