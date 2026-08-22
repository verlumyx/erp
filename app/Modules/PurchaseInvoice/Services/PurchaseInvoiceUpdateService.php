<?php

declare(strict_types=1);

namespace App\Modules\PurchaseInvoice\Services;

use App\Modules\ExchangeRate\Services\Contracts\DocumentRatesResolverInterface;
use App\Modules\PurchaseInvoice\Commands\UpdatePurchaseInvoiceCommand;
use App\Modules\PurchaseInvoice\Exceptions\PurchaseInvoiceNotFoundException;
use App\Modules\PurchaseInvoice\Models\PurchaseInvoice;
use App\Modules\PurchaseInvoice\Repositories\Contracts\PurchaseInvoiceRepositoryInterface;

class PurchaseInvoiceUpdateService
{
    public function __construct(
        private readonly PurchaseInvoiceRepositoryInterface $repository,
        private readonly DocumentRatesResolverInterface $rates,
        private readonly PurchaseInvoiceTermsService $terms,
    ) {}

    /**
     * Una factura solo se edita en borrador, así que cada guardado refresca las
     * tasas del catálogo. Al confirmarla quedan congeladas: es la deuda legal
     * con el proveedor y ya nada la recalcula.
     */
    public function execute(string $id, UpdatePurchaseInvoiceCommand $command, ?string $companyId = null): PurchaseInvoice
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new PurchaseInvoiceNotFoundException;
        }

        $company = $companyId ?? $model->company_id;

        $this->terms->guardSourceDocument(
            $command->sourceableType,
            $command->sourceableId,
            $command->supplierId,
            $company,
        );

        $rates = $this->rates->forDocument(
            $company,
            $command->currency,
            $command->invoiceDate,
            $command->exchangeRateOverride,
        );

        $dueDate = $this->terms->dueDate(
            $command->dueDate,
            $command->invoiceDate,
            $command->supplierId,
            $company,
        );

        $this->repository->update($model, $command, $rates, $dueDate);

        return $this->repository->findOrFail($id, $companyId);
    }
}
