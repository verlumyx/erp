<?php

declare(strict_types=1);

namespace App\Modules\Sale\Services;

use App\Modules\Sale\Commands\SearchSaleCommand;
use App\Modules\Sale\Repositories\Contracts\SaleRepositoryInterface;

class SaleSearchService
{
    public function __construct(
        private readonly SaleRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ data: mixed[], total: int }
     */
    public function execute(SearchSaleCommand $command): array
    {
        return $this->repository->search($command);
    }

    /**
     * @return array{ data: mixed[], total: int }
     */
    public function searchExpirations(SearchSaleCommand $command): array
    {
        return $this->repository->searchExpirations($command);
    }
}
