<?php

declare(strict_types=1);

namespace App\Modules\PurchaseCreditNote\Services;

use App\Modules\ExchangeRate\Services\Contracts\DocumentRatesResolverInterface;
use App\Modules\PurchaseCreditNote\Commands\CreatePurchaseCreditNoteCommand;
use App\Modules\PurchaseCreditNote\Models\PurchaseCreditNote;
use App\Modules\PurchaseCreditNote\Repositories\Contracts\PurchaseCreditNoteRepositoryInterface;

class PurchaseCreditNoteCreateService
{
    public function __construct(
        private readonly PurchaseCreditNoteRepositoryInterface $repository,
        private readonly DocumentRatesResolverInterface $rates,
        private readonly PurchaseCreditNoteLimitsService $limits,
        private readonly PurchaseCreditNoteReturnSyncService $returns,
    ) {}

    /**
     * La nota se valora con la tasa de **su** fecha, no con la de la factura
     * que corrige: son dos documentos y cada uno congela la suya. Si falta la
     * tasa del día la nota no se emite.
     */
    public function execute(CreatePurchaseCreditNoteCommand $command): PurchaseCreditNote
    {
        $this->limits->guard(
            $command->purchaseInvoiceId,
            $command->supplierId,
            $command->companyId,
            $command->lines,
            $command->id,
        );

        $rates = $this->rates->forDocument(
            $command->companyId,
            $command->currency,
            $command->noteDate,
            $command->exchangeRateOverride,
        );

        $this->repository->create($command, $rates);

        $note = $this->repository->findOrFail($command->id);

        /** Nacer apuntando a una devolución la deja acreditada. */
        $this->returns->sync($note);

        return $note;
    }
}
