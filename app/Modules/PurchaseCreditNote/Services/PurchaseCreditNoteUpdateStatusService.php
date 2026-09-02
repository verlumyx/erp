<?php

declare(strict_types=1);

namespace App\Modules\PurchaseCreditNote\Services;

use App\Modules\PurchaseCreditNote\Commands\UpdateStatusPurchaseCreditNoteCommand;
use App\Modules\PurchaseCreditNote\Exceptions\PurchaseCreditNoteNotFoundException;
use App\Modules\PurchaseCreditNote\Models\PurchaseCreditNote;
use App\Modules\PurchaseCreditNote\Repositories\Contracts\PurchaseCreditNoteRepositoryInterface;
use Illuminate\Support\Facades\DB;

class PurchaseCreditNoteUpdateStatusService
{
    public function __construct(
        private readonly PurchaseCreditNoteRepositoryInterface $repository,
        private readonly PurchaseCreditNotePostingService $posting,
        private readonly PurchaseCreditNoteApplicationService $applications,
        private readonly PurchaseCreditNoteReturnSyncService $returns,
    ) {}

    /**
     * Cambiar el estado no toca las tasas ni los importes: confirmada, la nota
     * queda congelada tal como se guardó. Lo que sí mueve es la cuenta por
     * pagar del proveedor —y el inventario, si la mercancía sale—, y solo en
     * los dos momentos que importan: confirmarla y anularla ya confirmada.
     */
    public function execute(
        string $id,
        UpdateStatusPurchaseCreditNoteCommand $command,
        ?string $companyId = null,
    ): PurchaseCreditNote {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new PurchaseCreditNoteNotFoundException;
        }

        DB::transaction(function () use ($model, $command): void {
            $wasPosted = $model->status !== 'draft';

            if ($command->status === 'confirmed') {
                $this->posting->post($model);
            }

            /** Un borrador anulado no revierte nada: nunca llegó a acreditar. */
            if ($command->status === 'cancelled' && $wasPosted) {
                $this->posting->reverse($model);
                $this->applications->revert($model);
            }

            /** Anularla suelta la devolución que acreditaba: ese crédito ya no existe. */
            if ($command->status === 'cancelled') {
                $this->returns->release($model);
            }

            $this->repository->updateStatus($model, $command);

            /**
             * La nota gasta su crédito después de quedar confirmada, no antes:
             * aplicarla puede agotarla, y agotada su estado es `completed`.
             */
            if ($command->status === 'confirmed') {
                $this->applications->apply($model);
            }
        });

        return $this->repository->findOrFail($id, $companyId);
    }
}
