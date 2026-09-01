<?php

declare(strict_types=1);

namespace App\Modules\SalesReturn\Services;

use App\Modules\SalesReturn\Commands\SearchSalesReturnCommand;
use App\Modules\SalesReturn\Repositories\Contracts\SalesReturnRepositoryInterface;

class SalesReturnSearchService
{
    public function __construct(
        private readonly SalesReturnRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ data: mixed[], total: int }
     */
    public function execute(SearchSalesReturnCommand $command): array
    {
        return $this->repository->search($command);
    }
}
