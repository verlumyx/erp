<?php

declare(strict_types=1);

namespace App\Modules\LoadPlan\Services;

use App\Modules\LoadPlan\Commands\UpdateStatusLoadPlanCommand;
use App\Modules\LoadPlan\Exceptions\LoadPlanNotFoundException;
use App\Modules\LoadPlan\Models\LoadPlan;
use App\Modules\LoadPlan\Repositories\Contracts\LoadPlanRepositoryInterface;
use DomainException;

class LoadPlanUpdateStatusService
{
    public function __construct(
        private readonly LoadPlanRepositoryInterface $repository,
    ) {}

    public function execute(string $id, UpdateStatusLoadPlanCommand $command, ?string $companyId = null): LoadPlan
    {
        $loadPlan = $this->repository->findById($id, $companyId);

        if ($loadPlan === null) {
            throw new LoadPlanNotFoundException;
        }

        $allowedTransitions = LoadPlan::STATUS_TRANSITIONS[$loadPlan->status] ?? [];

        if (! in_array($command->status, $allowedTransitions, true)) {
            throw new DomainException("Transición de estado no permitida desde {$loadPlan->status} hacia {$command->status}.");
        }

        $this->repository->updateStatus($loadPlan, $command);

        return $loadPlan->refresh();
    }
}
