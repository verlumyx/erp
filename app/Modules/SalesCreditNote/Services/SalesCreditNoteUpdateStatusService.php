<?php

declare(strict_types=1);

namespace App\Modules\SalesCreditNote\Services;

use App\Modules\SalesCreditNote\Commands\UpdateStatusSalesCreditNoteCommand;
use App\Modules\SalesCreditNote\Exceptions\SalesCreditNoteNotFoundException;
use App\Modules\SalesCreditNote\Models\SalesCreditNote;
use App\Modules\SalesCreditNote\Repositories\Contracts\SalesCreditNoteRepositoryInterface;
use Illuminate\Support\Facades\DB;

class SalesCreditNoteUpdateStatusService
{
    public function __construct(
        private readonly SalesCreditNoteRepositoryInterface $repository,
        private readonly SalesCreditNoteApplicationService $applications,
        private readonly SalesCreditNoteReturnSyncService $returns,
    ) {}

    /**
     * Cambiar el estado no toca las tasas ni los importes: confirmada, la nota
     * queda congelada tal como se guardó. Lo que sí mueve es la cuenta por
     * cobrar del cliente, y solo en los dos momentos que importan: confirmarla
     * y anularla ya confirmada.
     *
     * La nota no mueve inventario. La mercancía que la motiva reingresa con su
     * Entrada, que es la que escribe el kardex.
     */
    public function execute(
        string $id,
        UpdateStatusSalesCreditNoteCommand $command,
        ?string $companyId = null,
    ): SalesCreditNote {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new SalesCreditNoteNotFoundException;
        }

        DB::transaction(function () use ($model, $command): void {
            $wasPosted = $model->status !== 'draft';

            /** Un borrador anulado no revierte nada: nunca gastó su crédito. */
            if ($command->status === 'cancelled' && $wasPosted) {
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
