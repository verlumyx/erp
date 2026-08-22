<?php

declare(strict_types=1);

namespace App\Modules\SalesInvoice\Services;

use App\Modules\SalesInvoice\Commands\UpdateStatusSalesInvoiceCommand;
use App\Modules\SalesInvoice\Exceptions\SalesInvoiceNotFoundException;
use App\Modules\SalesInvoice\Models\SalesInvoice;
use App\Modules\SalesInvoice\Repositories\Contracts\SalesInvoiceRepositoryInterface;

class SalesInvoiceUpdateStatusService
{
    public function __construct(
        private readonly SalesInvoiceRepositoryInterface $repository,
    ) {}

    /**
     * Cambiar de estado nunca vuelve a resolver las tasas: emitir la factura
     * es justo el momento en que quedan congeladas.
     */
    public function execute(string $id, UpdateStatusSalesInvoiceCommand $command, ?string $companyId = null): SalesInvoice
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new SalesInvoiceNotFoundException;
        }

        $this->repository->updateStatus($model, $command);

        return $this->repository->findOrFail($id, $companyId);
    }
}
