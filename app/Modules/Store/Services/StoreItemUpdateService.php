<?php

declare(strict_types=1);

namespace App\Modules\Store\Services;

use App\Modules\Store\Commands\UpdateStoreItemCommand;
use App\Modules\Store\Exceptions\StoreItemNotFoundException;
use App\Modules\Store\Models\StoreItem;
use App\Modules\Store\Repositories\Contracts\StoreItemRepositoryInterface;

class StoreItemUpdateService
{
    public function __construct(
        private readonly StoreItemRepositoryInterface $repository,
    ) {}

    public function execute(string $id, UpdateStoreItemCommand $command, ?string $companyId = null): StoreItem
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new StoreItemNotFoundException;
        }

        $this->repository->update($model, $command);

        return $this->repository->findOrFail($id, $companyId);
    }
}
