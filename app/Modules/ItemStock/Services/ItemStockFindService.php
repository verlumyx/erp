<?php

declare(strict_types=1);

namespace App\Modules\ItemStock\Services;

use App\Modules\ItemStock\Exceptions\ItemStockNotFoundException;
use App\Modules\ItemStock\Models\ItemStock;
use App\Modules\ItemStock\Repositories\Contracts\ItemStockRepositoryInterface;

class ItemStockFindService
{
    public function __construct(
        private readonly ItemStockRepositoryInterface $repository,
    ) {}

    public function execute(string $id, ?string $companyId = null): ItemStock
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new ItemStockNotFoundException;
        }

        return $model;
    }
}
