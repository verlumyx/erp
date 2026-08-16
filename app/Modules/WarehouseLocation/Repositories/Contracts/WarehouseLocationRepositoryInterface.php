<?php

declare(strict_types=1);

namespace App\Modules\WarehouseLocation\Repositories\Contracts;

use App\Modules\WarehouseLocation\Commands\CreateWarehouseLocationCommand;
use App\Modules\WarehouseLocation\Commands\SearchWarehouseLocationCommand;
use App\Modules\WarehouseLocation\Commands\UpdateStatusWarehouseLocationCommand;
use App\Modules\WarehouseLocation\Commands\UpdateWarehouseLocationCommand;
use App\Modules\WarehouseLocation\Models\WarehouseLocation;

interface WarehouseLocationRepositoryInterface
{
    public function create(CreateWarehouseLocationCommand $command): void;

    public function findById(string $id, ?string $companyId = null): ?WarehouseLocation;

    public function findOrFail(string $id, ?string $companyId = null): WarehouseLocation;

    public function update(WarehouseLocation $model, UpdateWarehouseLocationCommand $command): void;

    public function updateStatus(WarehouseLocation $model, UpdateStatusWarehouseLocationCommand $command): void;

    /** @return array{ data: WarehouseLocation[], total: int } */
    public function search(SearchWarehouseLocationCommand $command): array;
}
