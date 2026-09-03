<?php

declare(strict_types=1);

namespace App\Modules\Store\Services;

use App\Modules\Store\Commands\SearchStoreOrderCommand;
use App\Modules\Store\Repositories\Contracts\StoreOrderRepositoryInterface;

class StoreOrderSearchService
{
    public function __construct(
        private readonly StoreOrderRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ data: mixed[], total: int }
     */
    public function execute(SearchStoreOrderCommand $command): array
    {
        return $this->repository->search($command);
    }
}
