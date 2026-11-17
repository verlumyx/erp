<?php

declare(strict_types=1);

namespace App\Modules\LoadPlan\Services;

use App\Modules\LoadPlan\Repositories\Contracts\LoadPlanRepositoryInterface;

class LoadPlanPendingDispatchesService
{
    public function __construct(
        private readonly LoadPlanRepositoryInterface $repository,
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function execute(string $companyId, ?string $routeId = null, ?string $excludePlanId = null): array
    {
        return $this->repository->pendingDispatches($companyId, $routeId, $excludePlanId);
    }
}
