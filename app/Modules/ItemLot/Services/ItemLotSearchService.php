<?php

declare(strict_types=1);

namespace App\Modules\ItemLot\Services;

use App\Modules\ItemLot\Commands\SearchItemLotCommand;
use App\Modules\ItemLot\Repositories\Contracts\ItemLotRepositoryInterface;

class ItemLotSearchService
{
    public function __construct(
        private readonly ItemLotRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ data: mixed[], total: int }
     */
    public function execute(SearchItemLotCommand $command): array
    {
        return $this->repository->search($command);
    }
}
