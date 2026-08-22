<?php

declare(strict_types=1);

namespace App\Modules\PurchaseInvoice\Services;

use App\Modules\PurchaseInvoice\Commands\UpdateStatusPurchaseInvoiceCommand;
use App\Modules\PurchaseInvoice\Exceptions\PurchaseInvoiceNotFoundException;
use App\Modules\PurchaseInvoice\Models\PurchaseInvoice;
use App\Modules\PurchaseInvoice\Repositories\Contracts\PurchaseInvoiceRepositoryInterface;

class PurchaseInvoiceUpdateStatusService
{
    public function __construct(
        private readonly PurchaseInvoiceRepositoryInterface $repository,
    ) {}

    /**
     * Cambiar el estado no toca las tasas ni los importes: confirmada, la
     * factura queda congelada tal como se guardó.
     */
    public function execute(
        string $id,
        UpdateStatusPurchaseInvoiceCommand $command,
        ?string $companyId = null,
    ): PurchaseInvoice {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new PurchaseInvoiceNotFoundException;
        }

        $this->repository->updateStatus($model, $command);

        return $this->repository->findOrFail($id, $companyId);
    }
}
