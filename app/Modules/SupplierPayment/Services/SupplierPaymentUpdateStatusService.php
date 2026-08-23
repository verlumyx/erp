<?php

declare(strict_types=1);

namespace App\Modules\SupplierPayment\Services;

use App\Modules\SupplierPayment\Commands\UpdateStatusSupplierPaymentCommand;
use App\Modules\SupplierPayment\Exceptions\SupplierPaymentNotFoundException;
use App\Modules\SupplierPayment\Models\SupplierPayment;
use App\Modules\SupplierPayment\Repositories\Contracts\SupplierPaymentRepositoryInterface;
use Illuminate\Support\Facades\DB;

class SupplierPaymentUpdateStatusService
{
    public function __construct(
        private readonly SupplierPaymentRepositoryInterface $repository,
        private readonly SupplierPaymentPostingService $posting,
    ) {}

    /**
     * Cambiar el estado no toca las tasas ni los importes del pago: lo que
     * mueve son los saldos de las facturas y del proveedor, y solo en los dos
     * momentos que importan —confirmarlo y anularlo ya confirmado—.
     */
    public function execute(
        string $id,
        UpdateStatusSupplierPaymentCommand $command,
        ?string $companyId = null,
    ): SupplierPayment {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new SupplierPaymentNotFoundException;
        }

        DB::transaction(function () use ($model, $command): void {
            $wasPosted = in_array($model->status, ['confirmed', 'completed'], true);

            if ($command->status === 'confirmed') {
                $this->posting->post($model);
            }

            /**
             * Un borrador anulado no revierte saldos —nunca llegó a abonar—,
             * pero sí libera al anticipo que lo generó, si vino de uno.
             */
            if ($command->status === 'cancelled') {
                $wasPosted ? $this->posting->reverse($model) : $this->posting->discard($model);
            }

            $this->repository->updateStatus($model, $command);
        });

        return $this->repository->findOrFail($id, $companyId);
    }
}
