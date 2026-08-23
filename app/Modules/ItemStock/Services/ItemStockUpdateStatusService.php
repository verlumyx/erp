<?php

declare(strict_types=1);

namespace App\Modules\ItemStock\Services;

use App\Modules\ItemStock\Commands\UpdateStatusItemStockCommand;
use App\Modules\ItemStock\Exceptions\ItemStockNotFoundException;
use App\Modules\ItemStock\Models\ItemStock;
use App\Modules\ItemStock\Repositories\Contracts\ItemStockRepositoryInterface;

class ItemStockUpdateStatusService
{
    public function __construct(
        private readonly ItemStockRepositoryInterface $repository,
    ) {}

    public function execute(string $id, UpdateStatusItemStockCommand $command, ?string $companyId = null): ItemStock
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new ItemStockNotFoundException;
        }

        $this->repository->updateStatus($model, $command);

        return $this->repository->findOrFail($id, $companyId);
    }
}
