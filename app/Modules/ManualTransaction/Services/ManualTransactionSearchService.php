<?php

declare(strict_types=1);

namespace App\Modules\ManualTransaction\Services;

use App\Modules\ManualTransaction\Commands\SearchManualTransactionCommand;
use App\Modules\ManualTransaction\Repositories\Contracts\ManualTransactionRepositoryInterface;

class ManualTransactionSearchService
{
    public function __construct(
        private readonly ManualTransactionRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ data: mixed[], total: int }
     */
    public function execute(SearchManualTransactionCommand $command): array
    {
        return $this->repository->search($command);
    }
}
