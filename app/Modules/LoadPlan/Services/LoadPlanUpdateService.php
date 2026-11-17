<?php

declare(strict_types=1);

namespace App\Modules\LoadPlan\Services;

use App\Modules\LoadPlan\Commands\UpdateLoadPlanCommand;
use App\Modules\LoadPlan\Exceptions\LoadPlanNotFoundException;
use App\Modules\LoadPlan\Models\LoadPlan;
use App\Modules\LoadPlan\Repositories\Contracts\LoadPlanRepositoryInterface;
use DomainException;

class LoadPlanUpdateService
{
    public function __construct(
        private readonly LoadPlanRepositoryInterface $repository,
    ) {}

    public function execute(string $id, UpdateLoadPlanCommand $command, ?string $companyId = null): LoadPlan
    {
        $loadPlan = $this->repository->findById($id, $companyId);

        if ($loadPlan === null) {
            throw new LoadPlanNotFoundException;
        }

        if (! $loadPlan->isEditable()) {
            throw new DomainException('No se puede modificar un plan de carga que no esté en estado borrador.');
        }

        $this->repository->update($loadPlan, $command);

        return $loadPlan->refresh();
    }
}
