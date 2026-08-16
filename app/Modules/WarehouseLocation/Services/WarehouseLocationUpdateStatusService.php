<?php

declare(strict_types=1);

namespace App\Modules\WarehouseLocation\Services;

use App\Modules\WarehouseLocation\Commands\UpdateStatusWarehouseLocationCommand;
use App\Modules\WarehouseLocation\Exceptions\WarehouseLocationNotFoundException;
use App\Modules\WarehouseLocation\Models\WarehouseLocation;
use App\Modules\WarehouseLocation\Repositories\Contracts\WarehouseLocationRepositoryInterface;

class WarehouseLocationUpdateStatusService
{
    public function __construct(
        private readonly WarehouseLocationRepositoryInterface $repository,
    ) {}

    public function execute(string $id, UpdateStatusWarehouseLocationCommand $command, ?string $companyId = null): WarehouseLocation
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new WarehouseLocationNotFoundException;
        }

        $this->repository->updateStatus($model, $command);

        return $this->repository->findOrFail($id, $companyId);
    }
}
