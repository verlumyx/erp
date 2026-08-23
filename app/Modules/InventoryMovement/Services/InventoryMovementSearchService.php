<?php

declare(strict_types=1);

namespace App\Modules\InventoryMovement\Services;

use App\Modules\InventoryMovement\Commands\SearchInventoryMovementCommand;
use App\Modules\InventoryMovement\Repositories\Contracts\InventoryMovementRepositoryInterface;

class InventoryMovementSearchService
{
    public function __construct(
        private readonly InventoryMovementRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ data: mixed[], total: int }
     */
    public function execute(SearchInventoryMovementCommand $command): array
    {
        return $this->repository->search($command);
    }
}
