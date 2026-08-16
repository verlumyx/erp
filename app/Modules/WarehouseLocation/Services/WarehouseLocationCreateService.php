<?php

declare(strict_types=1);

namespace App\Modules\WarehouseLocation\Services;

use App\Modules\WarehouseLocation\Commands\CreateWarehouseLocationCommand;
use App\Modules\WarehouseLocation\Models\WarehouseLocation;
use App\Modules\WarehouseLocation\Repositories\Contracts\WarehouseLocationRepositoryInterface;

class WarehouseLocationCreateService
{
    public function __construct(
        private readonly WarehouseLocationRepositoryInterface $repository,
    ) {}

    public function execute(CreateWarehouseLocationCommand $command): WarehouseLocation
    {
        $this->repository->create($command);

        return $this->repository->findOrFail($command->id);
    }
}
