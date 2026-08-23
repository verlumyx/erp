<?php

declare(strict_types=1);

namespace App\Modules\PurchaseCreditNote\Services;

use App\Modules\ExchangeRate\Services\Contracts\DocumentRatesResolverInterface;
use App\Modules\PurchaseCreditNote\Commands\UpdatePurchaseCreditNoteCommand;
use App\Modules\PurchaseCreditNote\Exceptions\PurchaseCreditNoteNotFoundException;
use App\Modules\PurchaseCreditNote\Models\PurchaseCreditNote;
use App\Modules\PurchaseCreditNote\Repositories\Contracts\PurchaseCreditNoteRepositoryInterface;

class PurchaseCreditNoteUpdateService
{
    public function __construct(
        private readonly PurchaseCreditNoteRepositoryInterface $repository,
        private readonly DocumentRatesResolverInterface $rates,
        private readonly PurchaseCreditNoteLimitsService $limits,
        private readonly PurchaseCreditNoteReturnSyncService $returns,
    ) {}

    /**
     * Una nota solo se edita en borrador, así que cada guardado refresca las
     * tasas del catálogo. Al confirmarla quedan congeladas: es el crédito que
     * el proveedor reconoce y ya nada lo recalcula.
     */
    public function execute(string $id, UpdatePurchaseCreditNoteCommand $command, ?string $companyId = null): PurchaseCreditNote
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new PurchaseCreditNoteNotFoundException;
        }

        $company = $companyId ?? $model->company_id;

        $this->limits->guard(
            $command->purchaseInvoiceId,
            $command->supplierId,
            $company,
            $command->lines,
            $model->id,
        );

        $rates = $this->rates->forDocument(
            $company,
            $command->currency,
            $command->noteDate,
            $command->exchangeRateOverride,
        );

        $previousReturnId = $model->purchase_return_id;

        $this->repository->update($model, $command, $rates);

        $note = $this->repository->findOrFail($id, $companyId);

        /** Repuntar la nota libera la devolución que acreditaba antes. */
        $this->returns->sync($note, $previousReturnId);

        return $note;
    }
}
