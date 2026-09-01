<?php

declare(strict_types=1);

namespace App\Modules\Adjustment\Services;

use App\Modules\Adjustment\Commands\SearchAdjustmentCommand;
use App\Modules\Adjustment\Repositories\Contracts\AdjustmentRepositoryInterface;

class AdjustmentSearchService
{
    public function __construct(
        private readonly AdjustmentRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ data: mixed[], total: int }
     */
    public function execute(SearchAdjustmentCommand $command): array
    {
        return $this->repository->search($command);
    }
}
