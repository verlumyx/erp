<?php

declare(strict_types=1);

namespace App\Modules\Supplier\Services;

use App\Modules\Supplier\Commands\UpdateStatusSupplierCommand;
use App\Modules\Supplier\Exceptions\SupplierNotFoundException;
use App\Modules\Supplier\Models\Supplier;
use App\Modules\Supplier\Repositories\Contracts\SupplierRepositoryInterface;

class SupplierUpdateStatusService
{
    public function __construct(
        private readonly SupplierRepositoryInterface $repository,
    ) {}

    public function execute(string $id, UpdateStatusSupplierCommand $command, ?string $companyId = null): Supplier
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new SupplierNotFoundException;
        }

        $this->repository->updateStatus($model, $command);

        return $this->repository->findOrFail($id, $companyId);
    }
}
