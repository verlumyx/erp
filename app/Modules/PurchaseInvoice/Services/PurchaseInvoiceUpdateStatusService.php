<?php

declare(strict_types=1);

namespace App\Modules\PurchaseInvoice\Services;

use App\Modules\PurchaseInvoice\Commands\UpdateStatusPurchaseInvoiceCommand;
use App\Modules\PurchaseInvoice\Exceptions\PurchaseInvoiceNotFoundException;
use App\Modules\PurchaseInvoice\Models\PurchaseInvoice;
use App\Modules\PurchaseInvoice\Repositories\Contracts\PurchaseInvoiceRepositoryInterface;
use Illuminate\Support\Facades\DB;

class PurchaseInvoiceUpdateStatusService
{
    public function __construct(
        private readonly PurchaseInvoiceRepositoryInterface $repository,
        private readonly PurchaseInvoicePostingService $posting,
    ) {}

    /**
     * Cambiar el estado no toca las tasas ni los importes: confirmada, la
     * factura queda congelada tal como se guardó. Lo que mueve es el saldo del
     * proveedor, lo facturado de la orden y el inventario, y solo en los dos
     * momentos que importan —confirmarla y anularla ya confirmada—.
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

        DB::transaction(function () use ($model, $command): void {
            $wasPosted = in_array($model->status, PurchaseInvoice::PAYABLE_STATUSES, true);

            if ($command->status === 'confirmed') {
                $this->posting->post($model);
            }

            /** Un borrador anulado no revierte nada: nunca llegó a deber. */
            if ($command->status === 'cancelled' && $wasPosted) {
                $this->posting->reverse($model);
            }

            $this->repository->updateStatus($model, $command);
        });

        return $this->repository->findOrFail($id, $companyId);
    }
}
