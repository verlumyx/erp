<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Services;

use App\Modules\Warehouse\Commands\CreateWarehouseCommand;
use App\Modules\Warehouse\Models\Warehouse;
use App\Modules\Warehouse\Repositories\Contracts\WarehouseRepositoryInterface;
use App\Modules\WarehouseLocation\Commands\CreateWarehouseLocationCommand;
use App\Modules\WarehouseLocation\Repositories\Contracts\WarehouseLocationRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WarehouseCreateService
{
    public function __construct(
        private readonly WarehouseRepositoryInterface $repository,
        private readonly WarehouseLocationRepositoryInterface $locationRepository,
    ) {}

    /**
     * Toda bodega nace con su ubicación "Principal": app_item_stocks.location_id
     * es obligatorio, así que no puede existir saldo sin ubicación.
     */
    public function execute(CreateWarehouseCommand $command): Warehouse
    {
        return DB::transaction(function () use ($command): Warehouse {
            $this->repository->create($command);

            $this->locationRepository->create(new CreateWarehouseLocationCommand(
                id: (string) Str::uuid7(),
                companyId: $command->companyId,
                warehouseId: $command->id,
                name: 'Principal',
                locationCode: Warehouse::DEFAULT_LOCATION_CODE,
                createdBy: $command->createdBy,
                parentId: null,
                type: 'zone',
                capacity: 0,
                isDefault: 'yes',
            ));

            return $this->repository->findOrFail($command->id);
        });
    }
}
