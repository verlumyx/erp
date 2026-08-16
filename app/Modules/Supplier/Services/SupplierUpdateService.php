<?php

declare(strict_types=1);

namespace App\Modules\Supplier\Services;

use App\Modules\Supplier\Commands\UpdateSupplierCommand;
use App\Modules\Supplier\Exceptions\SupplierNotFoundException;
use App\Modules\Supplier\Models\Supplier;
use App\Modules\Supplier\Repositories\Contracts\SupplierRepositoryInterface;

class SupplierUpdateService
{
    public function __construct(
        private readonly SupplierRepositoryInterface $repository,
    ) {}

    public function execute(string $id, UpdateSupplierCommand $command, ?string $companyId = null): Supplier
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new SupplierNotFoundException;
        }

        $this->repository->update($model, $command);

        return $this->repository->findOrFail($id, $companyId);
    }
}
