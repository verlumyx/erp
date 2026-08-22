<?php

declare(strict_types=1);

namespace App\Modules\SalesInvoice\Services;

use App\Modules\ExchangeRate\Services\Contracts\DocumentRatesResolverInterface;
use App\Modules\SalesInvoice\Commands\UpdateSalesInvoiceCommand;
use App\Modules\SalesInvoice\Exceptions\SalesInvoiceNotFoundException;
use App\Modules\SalesInvoice\Models\SalesInvoice;
use App\Modules\SalesInvoice\Repositories\Contracts\SalesInvoiceRepositoryInterface;

class SalesInvoiceUpdateService
{
    public function __construct(
        private readonly SalesInvoiceRepositoryInterface $repository,
        private readonly DocumentRatesResolverInterface $rates,
    ) {}

    /**
     * Una factura solo se edita en borrador, así que cada guardado refresca
     * las tasas del catálogo. Al emitirla quedan congeladas: ya nada las toca.
     */
    public function execute(string $id, UpdateSalesInvoiceCommand $command, ?string $companyId = null): SalesInvoice
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new SalesInvoiceNotFoundException;
        }

        $rates = $this->rates->forDocument(
            $companyId ?? $model->company_id,
            $command->currency,
            $command->invoiceDate,
            $command->exchangeRateOverride,
        );

        $this->repository->update($model, $command, $rates);

        return $this->repository->findOrFail($id, $companyId);
    }
}
