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
        private readonly PurchaseCreditNoteReturnSyncService $returns,
    ) {}

    /**
     * Cambiar el estado no toca las tasas ni los importes: confirmada, la nota
     * queda congelada tal como se guardó.
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
            /** Anularla suelta la devolución que acreditaba: ese crédito ya no existe. */
            if ($command->status === 'cancelled') {
                $this->returns->release($model);
            }

            $this->repository->updateStatus($model, $command);
        });

        return $this->repository->findOrFail($id, $companyId);
    }
}
