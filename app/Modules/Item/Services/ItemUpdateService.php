<?php

declare(strict_types=1);

namespace App\Modules\Item\Services;

use App\Modules\Item\Commands\UpdateItemCommand;
use App\Modules\Item\Exceptions\ItemNotFoundException;
use App\Modules\Item\Models\Item;
use App\Modules\Item\Repositories\Contracts\ItemRepositoryInterface;

class ItemUpdateService
{
    public function __construct(
        private readonly ItemRepositoryInterface $repository,
    ) {}

    public function execute(string $id, UpdateItemCommand $command, ?string $companyId = null): Item
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new ItemNotFoundException;
        }

        $this->repository->update($model, $command);

        return $this->repository->findOrFail($id, $companyId);
    }
}
