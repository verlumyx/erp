<?php

declare(strict_types=1);

namespace App\Modules\Transfer\Services;

use App\Modules\Transfer\Commands\SearchTransferCommand;
use App\Modules\Transfer\Repositories\Contracts\TransferRepositoryInterface;

class TransferSearchService
{
    public function __construct(
        private readonly TransferRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ data: mixed[], total: int }
     */
    public function execute(SearchTransferCommand $command): array
    {
        return $this->repository->search($command);
    }
}
