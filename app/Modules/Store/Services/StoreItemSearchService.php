<?php

declare(strict_types=1);

namespace App\Modules\Store\Services;

use App\Modules\Store\Commands\SearchStoreItemCommand;
use App\Modules\Store\Repositories\Contracts\StoreItemRepositoryInterface;

class StoreItemSearchService
{
    public function __construct(
        private readonly StoreItemRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ data: mixed[], total: int }
     */
    public function execute(SearchStoreItemCommand $command): array
    {
        return $this->repository->search($command);
    }
}
