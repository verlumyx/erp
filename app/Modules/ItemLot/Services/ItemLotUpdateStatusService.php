<?php

declare(strict_types=1);

namespace App\Modules\ItemLot\Services;

use App\Modules\ItemLot\Commands\UpdateStatusItemLotCommand;
use App\Modules\ItemLot\Exceptions\ItemLotNotFoundException;
use App\Modules\ItemLot\Models\ItemLot;
use App\Modules\ItemLot\Repositories\Contracts\ItemLotRepositoryInterface;

class ItemLotUpdateStatusService
{
    public function __construct(
        private readonly ItemLotRepositoryInterface $repository,
    ) {}

    public function execute(string $id, UpdateStatusItemLotCommand $command, ?string $companyId = null): ItemLot
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new ItemLotNotFoundException;
        }

        $this->repository->updateStatus($model, $command);

        return $this->repository->findOrFail($id, $companyId);
    }
}
