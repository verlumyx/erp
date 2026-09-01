<?php

declare(strict_types=1);

namespace App\Modules\SalesCreditNote\Services;

use App\Modules\ExchangeRate\Services\Contracts\DocumentRatesResolverInterface;
use App\Modules\SalesCreditNote\Commands\UpdateSalesCreditNoteCommand;
use App\Modules\SalesCreditNote\Exceptions\SalesCreditNoteNotFoundException;
use App\Modules\SalesCreditNote\Models\SalesCreditNote;
use App\Modules\SalesCreditNote\Repositories\Contracts\SalesCreditNoteRepositoryInterface;

class SalesCreditNoteUpdateService
{
    public function __construct(
        private readonly SalesCreditNoteRepositoryInterface $repository,
        private readonly DocumentRatesResolverInterface $rates,
        private readonly SalesCreditNoteLimitsService $limits,
        private readonly SalesCreditNoteReturnSyncService $returns,
    ) {}

    /**
     * Una nota solo se edita en borrador, así que cada guardado refresca las
     * tasas del catálogo. Al confirmarla quedan congeladas: es el crédito que
     * la empresa reconoce al cliente y ya nada lo recalcula.
     */
    public function execute(string $id, UpdateSalesCreditNoteCommand $command, ?string $companyId = null): SalesCreditNote
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new SalesCreditNoteNotFoundException;
        }

        $company = $companyId ?? $model->company_id;

        $this->limits->guard(
            $command->salesInvoiceId,
            $command->clientId,
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

        $previousReturnId = $model->sales_return_id;

        $this->repository->update($model, $command, $rates);

        $note = $this->repository->findOrFail($id, $companyId);

        /** Repuntar la nota libera la devolución que acreditaba antes. */
        $this->returns->sync($note, $previousReturnId);

        return $note;
    }
}
