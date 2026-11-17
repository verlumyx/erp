<?php

declare(strict_types=1);

namespace App\Modules\LoadPlan\Services;

use App\Modules\LoadPlan\Commands\SearchLoadPlanCommand;
use App\Modules\LoadPlan\Models\LoadPlan;
use App\Modules\LoadPlan\Repositories\Contracts\LoadPlanRepositoryInterface;

class LoadPlanSearchService
{
    public function __construct(
        private readonly LoadPlanRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ data: array<int, LoadPlan>, total: int }
     */
    public function execute(SearchLoadPlanCommand $command): array
    {
        return $this->repository->search($command);
    }
}
