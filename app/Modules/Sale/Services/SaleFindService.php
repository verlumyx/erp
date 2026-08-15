<?php

declare(strict_types=1);

namespace App\Modules\Sale\Services;

use App\Modules\Sale\Exceptions\SaleNotFoundException;
use App\Modules\Sale\Models\Sale;
use App\Modules\Sale\Repositories\Contracts\SaleRepositoryInterface;

class SaleFindService
{
    public function __construct(
        private readonly SaleRepositoryInterface $repository,
    ) {}

    public function execute(string $id, ?string $companyId = null): Sale
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new SaleNotFoundException;
        }

        return $model;
    }
}
