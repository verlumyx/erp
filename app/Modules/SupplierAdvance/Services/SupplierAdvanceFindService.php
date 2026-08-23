<?php

declare(strict_types=1);

namespace App\Modules\SupplierAdvance\Services;

use App\Modules\SupplierAdvance\Exceptions\SupplierAdvanceNotFoundException;
use App\Modules\SupplierAdvance\Models\SupplierAdvance;
use App\Modules\SupplierAdvance\Repositories\Contracts\SupplierAdvanceRepositoryInterface;

class SupplierAdvanceFindService
{
    public function __construct(
        private readonly SupplierAdvanceRepositoryInterface $repository,
    ) {}

    public function execute(string $id, ?string $companyId = null): SupplierAdvance
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new SupplierAdvanceNotFoundException;
        }

        return $model;
    }
}
