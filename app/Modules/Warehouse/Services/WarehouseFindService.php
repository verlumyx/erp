<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Services;

use App\Modules\Warehouse\Exceptions\WarehouseNotFoundException;
use App\Modules\Warehouse\Models\Warehouse;
use App\Modules\Warehouse\Repositories\Contracts\WarehouseRepositoryInterface;

class WarehouseFindService
{
    public function __construct(
        private readonly WarehouseRepositoryInterface $repository,
    ) {}

    public function execute(string $id, ?string $companyId = null): Warehouse
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new WarehouseNotFoundException;
        }

        return $model;
    }
}
