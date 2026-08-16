<?php

declare(strict_types=1);

namespace App\Modules\SalesOrder\Services;

use App\Modules\SalesOrder\Commands\UpdateStatusSalesOrderCommand;
use App\Modules\SalesOrder\Exceptions\SalesOrderNotFoundException;
use App\Modules\SalesOrder\Models\SalesOrder;
use App\Modules\SalesOrder\Repositories\Contracts\SalesOrderRepositoryInterface;

class SalesOrderUpdateStatusService
{
    public function __construct(
        private readonly SalesOrderRepositoryInterface $repository,
    ) {}

    public function execute(string $id, UpdateStatusSalesOrderCommand $command, ?string $companyId = null): SalesOrder
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new SalesOrderNotFoundException;
        }

        $this->repository->updateStatus($model, $command);

        return $this->repository->findOrFail($id, $companyId);
    }
}
