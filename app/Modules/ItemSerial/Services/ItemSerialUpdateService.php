<?php

declare(strict_types=1);

namespace App\Modules\ItemSerial\Services;

use App\Modules\ItemSerial\Commands\UpdateItemSerialCommand;
use App\Modules\ItemSerial\Exceptions\ItemSerialNotFoundException;
use App\Modules\ItemSerial\Models\ItemSerial;
use App\Modules\ItemSerial\Repositories\Contracts\ItemSerialRepositoryInterface;

class ItemSerialUpdateService
{
    public function __construct(
        private readonly ItemSerialRepositoryInterface $repository,
    ) {}

    public function execute(string $id, UpdateItemSerialCommand $command, ?string $companyId = null): ItemSerial
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new ItemSerialNotFoundException;
        }

        $this->repository->update($model, $command);

        return $this->repository->findOrFail($id, $companyId);
    }
}
