<?php

declare(strict_types=1);

namespace App\Modules\SupplierType\Repositories\Contracts;

use App\Modules\SupplierType\Commands\CreateSupplierTypeCommand;
use App\Modules\SupplierType\Commands\SearchSupplierTypeCommand;
use App\Modules\SupplierType\Commands\UpdateStatusSupplierTypeCommand;
use App\Modules\SupplierType\Commands\UpdateSupplierTypeCommand;
use App\Modules\SupplierType\Models\SupplierType;

interface SupplierTypeRepositoryInterface
{
    public function create(CreateSupplierTypeCommand $command): void;

    public function findById(string $id, ?string $companyId = null): ?SupplierType;

    public function findOrFail(string $id, ?string $companyId = null): SupplierType;

    public function update(SupplierType $model, UpdateSupplierTypeCommand $command): void;

    public function updateStatus(SupplierType $model, UpdateStatusSupplierTypeCommand $command): void;

    /** @return array{ data: SupplierType[], total: int } */
    public function search(SearchSupplierTypeCommand $command): array;
}
