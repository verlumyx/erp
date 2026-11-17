<?php

declare(strict_types=1);

namespace App\Modules\LoadPlan\Services;

use App\Modules\Dispatch\Commands\UpdateStatusDispatchCommand;
use App\Modules\Dispatch\Services\DispatchUpdateStatusService;
use App\Modules\LoadPlan\Commands\UpdateStatusLoadPlanCommand;
use App\Modules\LoadPlan\Exceptions\LoadPlanNotFoundException;
use App\Modules\LoadPlan\Models\LoadPlan;
use App\Modules\LoadPlan\Repositories\Contracts\LoadPlanRepositoryInterface;
use DomainException;
use Illuminate\Support\Facades\DB;

class LoadPlanUpdateStatusService
{
    public function __construct(
        private readonly LoadPlanRepositoryInterface $repository,
        private readonly DispatchUpdateStatusService $dispatchUpdateStatusService,
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

        $effectiveCompanyId = $companyId ?? (string) $loadPlan->company_id;

        DB::transaction(function () use ($loadPlan, $command, $effectiveCompanyId): void {
            // 1. Al pasar a "in_transit" (Despachar a tránsito):
            // Los despachos asociados en estado "pending" pasan a "confirmed" (Despachado).
            if ($command->status === 'in_transit') {
                $dispatches = $loadPlan->dispatches()->get();
                foreach ($dispatches as $dispatch) {
                    if ($dispatch->status === 'pending') {
                        $this->dispatchUpdateStatusService->execute(
                            $dispatch->id,
                            new UpdateStatusDispatchCommand('confirmed'),
                            $effectiveCompanyId,
                        );
                    }
                }
            }

            // 2. Al pasar a "confirmed" (Confirmar entrega):
            // Los despachos seleccionados como entregados pasan a "delivered".
            // Los que no se entregaron se quedan en "confirmed" (Despachado) para posible devolución.
            if ($command->status === 'confirmed') {
                $dispatches = $loadPlan->dispatches()->get();
                $deliveredIds = $command->deliveredDispatchIds;

                foreach ($dispatches as $dispatch) {
                    $shouldDeliver = $deliveredIds === null || in_array($dispatch->id, $deliveredIds, true);

                    if ($shouldDeliver && in_array($dispatch->status, ['confirmed', 'pending'], true)) {
                        if ($dispatch->status === 'pending') {
                            $this->dispatchUpdateStatusService->execute(
                                $dispatch->id,
                                new UpdateStatusDispatchCommand('confirmed'),
                                $effectiveCompanyId,
                            );
                        }
                        $this->dispatchUpdateStatusService->execute(
                            $dispatch->id,
                            new UpdateStatusDispatchCommand('delivered'),
                            $effectiveCompanyId,
                        );
                    }
                }
            }

            $this->repository->updateStatus($loadPlan, $command);
        });

        return $loadPlan->refresh();
    }
}
