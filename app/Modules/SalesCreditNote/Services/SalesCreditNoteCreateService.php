<?php

declare(strict_types=1);

namespace App\Modules\SalesCreditNote\Services;

use App\Modules\ExchangeRate\Services\Contracts\DocumentRatesResolverInterface;
use App\Modules\SalesCreditNote\Commands\CreateSalesCreditNoteCommand;
use App\Modules\SalesCreditNote\Models\SalesCreditNote;
use App\Modules\SalesCreditNote\Repositories\Contracts\SalesCreditNoteRepositoryInterface;

class SalesCreditNoteCreateService
{
    public function __construct(
        private readonly SalesCreditNoteRepositoryInterface $repository,
        private readonly DocumentRatesResolverInterface $rates,
        private readonly SalesCreditNoteLimitsService $limits,
        private readonly SalesCreditNoteReturnSyncService $returns,
    ) {}

    /**
     * La nota se valora con la tasa de **su** fecha, no con la de la factura
     * que corrige: son dos documentos y cada uno congela la suya. Si falta la
     * tasa del día la nota no se emite.
     */
    public function execute(CreateSalesCreditNoteCommand $command): SalesCreditNote
    {
        $this->limits->guard(
            $command->salesInvoiceId,
            $command->clientId,
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
