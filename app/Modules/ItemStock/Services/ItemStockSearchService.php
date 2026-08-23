<?php

declare(strict_types=1);

namespace App\Modules\ItemStock\Services;

use App\Modules\ItemStock\Commands\SearchItemStockCommand;
use App\Modules\ItemStock\Repositories\Contracts\ItemStockRepositoryInterface;

class ItemStockSearchService
{
    public function __construct(
        private readonly ItemStockRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ data: mixed[], total: int }
     */
    public function execute(SearchItemStockCommand $command): array
    {
        return $this->repository->search($command);
    }
}
