<?php

declare(strict_types=1);

namespace App\Modules\Adjustment\Services;

use App\Modules\Adjustment\Commands\UpdateStatusAdjustmentCommand;
use App\Modules\Adjustment\Exceptions\AdjustmentNotFoundException;
use App\Modules\Adjustment\Models\Adjustment;
use App\Modules\Adjustment\Repositories\Contracts\AdjustmentRepositoryInterface;
use App\Modules\Import\Services\ImportApplyAdjustmentService;
use Illuminate\Support\Facades\DB;

class AdjustmentUpdateStatusService
{
    public function __construct(
        private readonly AdjustmentRepositoryInterface $repository,
        private readonly AdjustmentPostingService $posting,
        private readonly AdjustmentApprovalService $approval,
        private readonly ImportApplyAdjustmentService $imports,
    ) {}

    /**
     * Cambiar el estado no toca lo contado: lo que mueve es el inventario, y
     * solo en los dos momentos que importan —confirmarlo y anularlo ya
     * confirmado—.
     */
    public function execute(
        string $id,
        UpdateStatusAdjustmentCommand $command,
        ?string $companyId = null,
    ): Adjustment {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new AdjustmentNotFoundException;
        }

        DB::transaction(function () use ($model, $command): void {
            $wasPosted = in_array($model->status, Adjustment::POSTED_STATUSES, true);

            if ($command->status === 'confirmed') {
                $this->approval->guard($model, $command->approvedBy);
                $this->posting->post($model);
            }

            /** Un ajuste anulado antes de aplicarse no revierte nada: nunca movió existencia. */
            if ($command->status === 'cancelled' && $wasPosted) {
                $this->posting->reverse($model);
            }

            /**
             * El expediente de importación que lo generó, si lo hay, se cierra
             * con él: el costo lo estimó el expediente, pero quien lo escribe
             * contra la existencia del momento es este ajuste.
             */
            if ($command->status === 'confirmed') {
                $this->imports->markSettled($model);
            }

            if ($command->status === 'cancelled' && $wasPosted) {
                $this->imports->markSettled($model, false);
            }

            $this->repository->updateStatus($model, $command);
        });

        return $this->repository->findOrFail($id, $companyId);
    }
}
