<?php

declare(strict_types=1);

namespace App\Modules\SalesOrder\Services;

use App\Modules\SalesOrder\Exceptions\SalesOrderNotFoundException;
use App\Modules\SalesOrder\Models\SalesOrder;
use App\Modules\SalesOrder\Repositories\Contracts\SalesOrderRepositoryInterface;

class SalesOrderFindService
{
    public function __construct(
        private readonly SalesOrderRepositoryInterface $repository,
    ) {}

    public function execute(string $id, ?string $companyId = null): SalesOrder
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new SalesOrderNotFoundException;
        }

        return $model;
    }
}
