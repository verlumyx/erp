<?php

declare(strict_types=1);

namespace App\Modules\SalesInvoice\Services;

use App\Modules\ExchangeRate\Services\Contracts\DocumentRatesResolverInterface;
use App\Modules\SalesInvoice\Commands\CreateSalesInvoiceCommand;
use App\Modules\SalesInvoice\Models\SalesInvoice;
use App\Modules\SalesInvoice\Repositories\Contracts\SalesInvoiceRepositoryInterface;

class SalesInvoiceCreateService
{
    public function __construct(
        private readonly SalesInvoiceRepositoryInterface $repository,
        private readonly DocumentRatesResolverInterface $rates,
    ) {}

    /**
     * Las tasas salen del catálogo, nunca del formulario. Si falta la del día
     * la factura no se emite: es preferible no poder facturar a facturar con
     * una tasa inventada.
     */
    public function execute(CreateSalesInvoiceCommand $command): SalesInvoice
    {
        $rates = $this->rates->forDocument(
            $command->companyId,
            $command->currency,
            $command->invoiceDate,
            $command->exchangeRateOverride,
        );

        $this->repository->create($command, $rates);

        return $this->repository->findOrFail($command->id);
    }
}
