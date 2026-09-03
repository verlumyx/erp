<?php

declare(strict_types=1);

namespace App\Modules\Store\Services;

use App\Modules\Store\Exceptions\StoreCustomerNotFoundException;
use App\Modules\Store\Models\StoreCustomer;
use App\Modules\Store\Repositories\Contracts\StoreCustomerRepositoryInterface;

class StoreCustomerFindService
{
    public function __construct(
        private readonly StoreCustomerRepositoryInterface $repository,
    ) {}

    public function execute(string $id, ?string $companyId = null): StoreCustomer
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new StoreCustomerNotFoundException;
        }

        return $model;
    }
}
