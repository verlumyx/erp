<?php

declare(strict_types=1);

namespace App\Modules\Store\Services;

use App\Modules\Store\Exceptions\StoreOrderNotFoundException;
use App\Modules\Store\Models\StoreOrder;
use App\Modules\Store\Repositories\Contracts\StoreOrderRepositoryInterface;

class StoreOrderFindService
{
    public function __construct(
        private readonly StoreOrderRepositoryInterface $repository,
    ) {}

    public function execute(string $id, ?string $companyId = null): StoreOrder
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new StoreOrderNotFoundException;
        }

        return $model;
    }
}
