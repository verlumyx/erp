<?php

declare(strict_types=1);

namespace App\Modules\PurchaseReturn\Services;

use App\Modules\PurchaseReturn\Commands\SearchPurchaseReturnCommand;
use App\Modules\PurchaseReturn\Repositories\Contracts\PurchaseReturnRepositoryInterface;

class PurchaseReturnSearchService
{
    public function __construct(
        private readonly PurchaseReturnRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ data: mixed[], total: int }
     */
    public function execute(SearchPurchaseReturnCommand $command): array
    {
        return $this->repository->search($command);
    }
}
