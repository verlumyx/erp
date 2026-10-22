<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Services;

use App\Modules\Warehouse\Commands\CreateWarehouseCommand;
use App\Modules\Warehouse\Commands\UpdateWarehouseCommand;
use App\Modules\Warehouse\Models\Warehouse;
use App\Modules\Warehouse\Repositories\Contracts\WarehouseRepositoryInterface;
use Illuminate\Support\Str;

class WarehouseProvisionService
{
    public const DEFAULT_WAREHOUSE_NAME = 'Bodega general';

    public function __construct(
        private readonly WarehouseCreateService $createService,
        private readonly WarehouseRepositoryInterface $repository,
    ) {}

    /**
     * Provision default warehouse for a company if it does not already exist.
     * Ensures "Bodega general" exists with uses_locations = 'yes' and is_default = 'yes'.
     */
    public function provisionDefaults(string $companyId, ?string $userId = null): Warehouse
    {
        $existing = Warehouse::query()
            ->where('company_id', $companyId)
            ->whereRaw('LOWER(name) = ?', [strtolower(self::DEFAULT_WAREHOUSE_NAME)])
            ->first();

        if ($existing !== null) {
            if ($existing->is_default !== 'yes' || $existing->uses_locations !== 'yes') {
                $this->repository->update($existing, new UpdateWarehouseCommand(
                    name: $existing->name,
                    type: $existing->type,
                    address: $existing->address,
                    phone: $existing->phone,
                    city: $existing->city,
                    responsibleUserId: $existing->responsible_user_id,
                    isDefault: 'yes',
                    allowsNegativeStock: $existing->allows_negative_stock,
                    usesLocations: 'yes',
                    isSalesAvailable: $existing->is_sales_available,
                    notes: $existing->notes,
                ));
            }

            return $this->repository->findOrFail($existing->id, $companyId);
        }

        $id = Str::uuid7()->toString();

        return $this->createService->execute(new CreateWarehouseCommand(
            id: $id,
            companyId: $companyId,
            name: self::DEFAULT_WAREHOUSE_NAME,
            createdBy: $userId,
            type: 'main',
            isDefault: 'yes',
            allowsNegativeStock: 'no',
            usesLocations: 'yes',
            isSalesAvailable: 'yes',
            notes: 'Bodega general principal de almacenamiento',
        ));
    }
}
