<?php

declare(strict_types=1);

namespace App\Modules\Item\Services;

use App\Modules\Item\Commands\SearchItemCommand;
use App\Modules\Item\Repositories\Contracts\ItemRepositoryInterface;

class ItemSearchService
{
    public function __construct(
        private readonly ItemRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ data: mixed[], total: int }
     */
    public function execute(SearchItemCommand $command): array
    {
        return $this->repository->search($command);
    }
}
