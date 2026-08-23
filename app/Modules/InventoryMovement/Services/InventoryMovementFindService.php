<?php

declare(strict_types=1);

namespace App\Modules\InventoryMovement\Services;

use App\Modules\InventoryMovement\Exceptions\InventoryMovementNotFoundException;
use App\Modules\InventoryMovement\Models\InventoryMovement;
use App\Modules\InventoryMovement\Repositories\Contracts\InventoryMovementRepositoryInterface;

class InventoryMovementFindService
{
    public function __construct(
        private readonly InventoryMovementRepositoryInterface $repository,
    ) {}

    public function execute(string $id, ?string $companyId = null): InventoryMovement
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new InventoryMovementNotFoundException;
        }

        return $model;
    }
}
