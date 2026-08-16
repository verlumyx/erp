<?php

declare(strict_types=1);

namespace App\Modules\SupplierType\Services;

use App\Modules\SupplierType\Commands\UpdateSupplierTypeCommand;
use App\Modules\SupplierType\Exceptions\SupplierTypeNotFoundException;
use App\Modules\SupplierType\Models\SupplierType;
use App\Modules\SupplierType\Repositories\Contracts\SupplierTypeRepositoryInterface;

class SupplierTypeUpdateService
{
    public function __construct(
        private readonly SupplierTypeRepositoryInterface $repository,
    ) {}

    public function execute(string $id, UpdateSupplierTypeCommand $command, ?string $companyId = null): SupplierType
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new SupplierTypeNotFoundException;
        }

        $this->repository->update($model, $command);

        return $this->repository->findOrFail($id, $companyId);
    }
}
