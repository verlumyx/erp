<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Repositories\Contracts;

use App\Modules\Warehouse\Commands\CreateWarehouseCommand;
use App\Modules\Warehouse\Commands\SearchWarehouseCommand;
use App\Modules\Warehouse\Commands\UpdateStatusWarehouseCommand;
use App\Modules\Warehouse\Commands\UpdateWarehouseCommand;
use App\Modules\Warehouse\Models\Warehouse;

interface WarehouseRepositoryInterface
{
    public function create(CreateWarehouseCommand $command): void;

    public function findById(string $id, ?string $companyId = null): ?Warehouse;

    public function findOrFail(string $id, ?string $companyId = null): Warehouse;

    public function update(Warehouse $model, UpdateWarehouseCommand $command): void;

    public function updateStatus(Warehouse $model, UpdateStatusWarehouseCommand $command): void;

    /** @return array{ data: Warehouse[], total: int } */
    public function search(SearchWarehouseCommand $command): array;
}
