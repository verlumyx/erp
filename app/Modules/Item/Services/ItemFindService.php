<?php

declare(strict_types=1);

namespace App\Modules\Item\Services;

use App\Modules\Item\Exceptions\ItemNotFoundException;
use App\Modules\Item\Models\Item;
use App\Modules\Item\Repositories\Contracts\ItemRepositoryInterface;

class ItemFindService
{
    public function __construct(
        private readonly ItemRepositoryInterface $repository,
    ) {}

    public function execute(string $id, ?string $companyId = null): Item
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new ItemNotFoundException;
        }

        return $model;
    }
}
