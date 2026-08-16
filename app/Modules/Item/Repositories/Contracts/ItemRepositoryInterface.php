<?php

declare(strict_types=1);

namespace App\Modules\Item\Repositories\Contracts;

use App\Modules\Item\Commands\CreateItemCommand;
use App\Modules\Item\Commands\SearchItemCommand;
use App\Modules\Item\Commands\UpdateItemCommand;
use App\Modules\Item\Commands\UpdateStatusItemCommand;
use App\Modules\Item\Models\Item;

interface ItemRepositoryInterface
{
    public function create(CreateItemCommand $command): void;

    public function findById(string $id, ?string $companyId = null): ?Item;

    public function findOrFail(string $id, ?string $companyId = null): Item;

    public function update(Item $model, UpdateItemCommand $command): void;

    public function updateStatus(Item $model, UpdateStatusItemCommand $command): void;

    /** @return array{ data: Item[], total: int } */
    public function search(SearchItemCommand $command): array;
}
