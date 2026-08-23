<?php

declare(strict_types=1);

namespace App\Modules\ItemSerial\Services;

use App\Modules\ItemSerial\Exceptions\ItemSerialNotFoundException;
use App\Modules\ItemSerial\Models\ItemSerial;
use App\Modules\ItemSerial\Repositories\Contracts\ItemSerialRepositoryInterface;

class ItemSerialFindService
{
    public function __construct(
        private readonly ItemSerialRepositoryInterface $repository,
    ) {}

    public function execute(string $id, ?string $companyId = null): ItemSerial
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new ItemSerialNotFoundException;
        }

        return $model;
    }
}
