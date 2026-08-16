<?php

declare(strict_types=1);

namespace App\Modules\SalesOrder\Services;

use App\Modules\SalesOrder\Commands\CreateSalesOrderCommand;
use App\Modules\SalesOrder\Models\SalesOrder;
use App\Modules\SalesOrder\Repositories\Contracts\SalesOrderRepositoryInterface;

class SalesOrderCreateService
{
    public function __construct(
        private readonly SalesOrderRepositoryInterface $repository,
    ) {}

    public function execute(CreateSalesOrderCommand $command): SalesOrder
    {
        $this->repository->create($command);

        return $this->repository->findOrFail($command->id);
    }
}
