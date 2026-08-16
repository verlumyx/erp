<?php

declare(strict_types=1);

namespace App\Modules\Item\Services;

use App\Modules\Item\Commands\CreateItemCommand;
use App\Modules\Item\Models\Item;
use App\Modules\Item\Repositories\Contracts\ItemRepositoryInterface;

class ItemCreateService
{
    public function __construct(
        private readonly ItemRepositoryInterface $repository,
    ) {}

    public function execute(CreateItemCommand $command): Item
    {
        $this->repository->create($command);

        return $this->repository->findOrFail($command->id);
    }
}
