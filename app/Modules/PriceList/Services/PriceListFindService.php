<?php

declare(strict_types=1);

namespace App\Modules\PriceList\Services;

use App\Modules\PriceList\Exceptions\PriceListNotFoundException;
use App\Modules\PriceList\Models\PriceList;
use App\Modules\PriceList\Repositories\Contracts\PriceListRepositoryInterface;

class PriceListFindService
{
    public function __construct(
        private readonly PriceListRepositoryInterface $repository,
    ) {}

    public function execute(string $id, ?string $companyId = null): PriceList
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new PriceListNotFoundException;
        }

        return $model;
    }
}
