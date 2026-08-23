<?php

declare(strict_types=1);

namespace App\Modules\ItemSerial\Services;

use App\Modules\ItemSerial\Commands\UpdateStatusItemSerialCommand;
use App\Modules\ItemSerial\Exceptions\ItemSerialNotFoundException;
use App\Modules\ItemSerial\Models\ItemSerial;
use App\Modules\ItemSerial\Repositories\Contracts\ItemSerialRepositoryInterface;

class ItemSerialUpdateStatusService
{
    public function __construct(
        private readonly ItemSerialRepositoryInterface $repository,
    ) {}

    public function execute(string $id, UpdateStatusItemSerialCommand $command, ?string $companyId = null): ItemSerial
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new ItemSerialNotFoundException;
        }

        $this->repository->updateStatus($model, $command);

        return $this->repository->findOrFail($id, $companyId);
    }
}
