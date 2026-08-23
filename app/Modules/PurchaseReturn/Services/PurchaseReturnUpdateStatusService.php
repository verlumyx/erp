<?php

declare(strict_types=1);

namespace App\Modules\PurchaseReturn\Services;

use App\Modules\PurchaseReturn\Commands\UpdateStatusPurchaseReturnCommand;
use App\Modules\PurchaseReturn\Exceptions\PurchaseReturnNotFoundException;
use App\Modules\PurchaseReturn\Models\PurchaseReturn;
use App\Modules\PurchaseReturn\Repositories\Contracts\PurchaseReturnRepositoryInterface;
use Illuminate\Support\Facades\DB;

class PurchaseReturnUpdateStatusService
{
    public function __construct(
        private readonly PurchaseReturnRepositoryInterface $repository,
        private readonly PurchaseReturnPostingService $posting,
    ) {}

    /**
     * Cambiar el estado no toca las tasas ni los importes de la devolución: lo
     * que mueve es el inventario y el cupo devuelto de la factura, y solo en
     * los dos momentos que importan —confirmarla y anularla ya confirmada—.
     */
    public function execute(
        string $id,
        UpdateStatusPurchaseReturnCommand $command,
        ?string $companyId = null,
    ): PurchaseReturn {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new PurchaseReturnNotFoundException;
        }

        DB::transaction(function () use ($model, $command): void {
            $wasPosted = in_array($model->status, PurchaseReturn::POSTED_STATUSES, true);

            if ($command->status === 'confirmed') {
                $this->posting->post($model);
            }

            /** Un borrador anulado no revierte nada: nunca llegó a sacar mercancía. */
            if ($command->status === 'cancelled' && $wasPosted) {
                $this->posting->reverse($model);
            }

            $this->repository->updateStatus($model, $command);
        });

        return $this->repository->findOrFail($id, $companyId);
    }
}
