<?php

declare(strict_types=1);

namespace App\Modules\Store\Services;

use App\Modules\Store\Commands\SearchStoreCustomerCommand;
use App\Modules\Store\Repositories\Contracts\StoreCustomerRepositoryInterface;

class StoreCustomerSearchService
{
    public function __construct(
        private readonly StoreCustomerRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ data: mixed[], total: int }
     */
    public function execute(SearchStoreCustomerCommand $command): array
    {
        return $this->repository->search($command);
    }
}
