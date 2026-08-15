<?php

declare(strict_types=1);

namespace App\Modules\Refund\Services;

use App\Modules\Refund\Commands\SearchRefundCommand;
use App\Modules\Refund\Repositories\Contracts\RefundRepositoryInterface;

class RefundSearchService
{
    public function __construct(
        private readonly RefundRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ data: mixed[], total: int }
     */
    public function execute(SearchRefundCommand $command): array
    {
        return $this->repository->search($command);
    }
}
