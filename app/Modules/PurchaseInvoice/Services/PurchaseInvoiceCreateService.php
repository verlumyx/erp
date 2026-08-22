<?php

declare(strict_types=1);

namespace App\Modules\PurchaseInvoice\Services;

use App\Modules\ExchangeRate\Services\Contracts\DocumentRatesResolverInterface;
use App\Modules\PurchaseInvoice\Commands\CreatePurchaseInvoiceCommand;
use App\Modules\PurchaseInvoice\Models\PurchaseInvoice;
use App\Modules\PurchaseInvoice\Repositories\Contracts\PurchaseInvoiceRepositoryInterface;

class PurchaseInvoiceCreateService
{
    public function __construct(
        private readonly PurchaseInvoiceRepositoryInterface $repository,
        private readonly DocumentRatesResolverInterface $rates,
        private readonly PurchaseInvoiceTermsService $terms,
    ) {}

    /**
     * La factura se valora con la tasa de **su** fecha de emisión, no con la de
     * la orden que la originó: son dos documentos y cada uno congela la suya.
     * Si falta la tasa del día la factura no se emite.
     */
    public function execute(CreatePurchaseInvoiceCommand $command): PurchaseInvoice
    {
        $this->terms->guardSourceDocument(
            $command->sourceableType,
            $command->sourceableId,
            $command->supplierId,
            $command->companyId,
        );

        $rates = $this->rates->forDocument(
            $command->companyId,
            $command->currency,
            $command->invoiceDate,
            $command->exchangeRateOverride,
        );

        $dueDate = $this->terms->dueDate(
            $command->dueDate,
            $command->invoiceDate,
            $command->supplierId,
            $command->companyId,
        );

        $this->repository->create($command, $rates, $dueDate);

        return $this->repository->findOrFail($command->id);
    }
}
