<?php

declare(strict_types=1);

namespace App\Modules\Item\Services;

use App\Modules\Item\Commands\UpdateStatusItemCommand;
use App\Modules\Item\Exceptions\ItemNotFoundException;
use App\Modules\Item\Models\Item;
use App\Modules\Item\Repositories\Contracts\ItemRepositoryInterface;

class ItemUpdateStatusService
{
    public function __construct(
        private readonly ItemRepositoryInterface $repository,
    ) {}

    public function execute(string $id, UpdateStatusItemCommand $command, ?string $companyId = null): Item
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new ItemNotFoundException;
        }

        $this->repository->updateStatus($model, $command);

        return $this->repository->findOrFail($id, $companyId);
    }
}
