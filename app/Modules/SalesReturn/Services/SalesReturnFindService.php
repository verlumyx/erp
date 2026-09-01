<?php

declare(strict_types=1);

namespace App\Modules\SalesReturn\Services;

use App\Modules\SalesReturn\Exceptions\SalesReturnNotFoundException;
use App\Modules\SalesReturn\Models\SalesReturn;
use App\Modules\SalesReturn\Repositories\Contracts\SalesReturnRepositoryInterface;

class SalesReturnFindService
{
    public function __construct(
        private readonly SalesReturnRepositoryInterface $repository,
    ) {}

    public function execute(string $id, ?string $companyId = null): SalesReturn
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new SalesReturnNotFoundException;
        }

        return $model;
    }
}
