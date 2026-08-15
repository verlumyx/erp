<?php

declare(strict_types=1);

namespace App\Modules\Transaction\Services;

use App\Modules\Transaction\Commands\SearchTransactionCommand;
use App\Modules\Transaction\Repositories\Contracts\TransactionRepositoryInterface;

class TransactionSearchService
{
    public function __construct(
        private readonly TransactionRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ data: mixed[], total: int }
     */
    public function execute(SearchTransactionCommand $command): array
    {
        return $this->repository->search($command);
    }
}
