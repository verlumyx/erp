<?php

declare(strict_types=1);

namespace App\Modules\SalesReturn\Services;

use App\Modules\SalesReturn\Commands\UpdateStatusSalesReturnCommand;
use App\Modules\SalesReturn\Exceptions\SalesReturnNotFoundException;
use App\Modules\SalesReturn\Models\SalesReturn;
use App\Modules\SalesReturn\Repositories\Contracts\SalesReturnRepositoryInterface;
use Illuminate\Support\Facades\DB;

class SalesReturnUpdateStatusService
{
    public function __construct(
        private readonly SalesReturnRepositoryInterface $repository,
        private readonly SalesReturnPostingService $posting,
    ) {}

    /**
     * Cambiar el estado no toca las tasas ni los importes de la devolución: lo
     * que mueve es el inventario y el cupo devuelto de la factura, y solo en
     * los dos momentos que importan —confirmarla y anularla ya confirmada—.
     */
    public function execute(
        string $id,
        UpdateStatusSalesReturnCommand $command,
        ?string $companyId = null,
    ): SalesReturn {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new SalesReturnNotFoundException;
        }

        DB::transaction(function () use ($model, $command): void {
            $wasPosted = in_array($model->status, SalesReturn::POSTED_STATUSES, true);

            if ($command->status === 'confirmed') {
                $this->posting->post($model);
            }

            /** Un borrador anulado no revierte nada: nunca llegó a meter mercancía. */
            if ($command->status === 'cancelled' && $wasPosted) {
                $this->posting->reverse($model);
            }

            $this->repository->updateStatus($model, $command);
        });

        return $this->repository->findOrFail($id, $companyId);
    }
}
