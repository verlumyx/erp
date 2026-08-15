<?php

declare(strict_types=1);

namespace App\Modules\Plan\Services;

use App\Modules\Plan\Commands\SearchPlanCommand;
use App\Modules\Plan\Repositories\Contracts\PlanRepositoryInterface;

class PlanSearchService
{
    public function __construct(
        private readonly PlanRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ data: mixed[], total: int }
     */
    public function execute(SearchPlanCommand $command): array
    {
        return $this->repository->search($command);
    }
}
