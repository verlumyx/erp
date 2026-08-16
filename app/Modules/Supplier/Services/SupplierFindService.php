<?php

declare(strict_types=1);

namespace App\Modules\Supplier\Services;

use App\Modules\Supplier\Exceptions\SupplierNotFoundException;
use App\Modules\Supplier\Models\Supplier;
use App\Modules\Supplier\Repositories\Contracts\SupplierRepositoryInterface;

class SupplierFindService
{
    public function __construct(
        private readonly SupplierRepositoryInterface $repository,
    ) {}

    public function execute(string $id, ?string $companyId = null): Supplier
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new SupplierNotFoundException;
        }

        return $model;
    }
}
