<?php

declare(strict_types=1);

namespace App\Modules\SalesInvoice\Services;

use App\Modules\SalesInvoice\Commands\UpdateStatusSalesInvoiceCommand;
use App\Modules\SalesInvoice\Exceptions\SalesInvoiceNotFoundException;
use App\Modules\SalesInvoice\Models\SalesInvoice;
use App\Modules\SalesInvoice\Repositories\Contracts\SalesInvoiceRepositoryInterface;
use Illuminate\Support\Facades\DB;

class SalesInvoiceUpdateStatusService
{
    public function __construct(
        private readonly SalesInvoiceRepositoryInterface $repository,
        private readonly SalesInvoicePostingService $posting,
    ) {}

    /**
     * Cambiar de estado nunca vuelve a resolver las tasas: emitir la factura
     * es justo el momento en que quedan congeladas. Lo que sí mueve es el
     * inventario —y con él el costo de la mercancía vendida—, y solo en los dos
     * momentos que importan: emitirla y anularla ya emitida.
     */
    public function execute(string $id, UpdateStatusSalesInvoiceCommand $command, ?string $companyId = null): SalesInvoice
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new SalesInvoiceNotFoundException;
        }

        DB::transaction(function () use ($model, $command): void {
            $wasPosted = $model->status !== 'draft';

            /**
             * El inventario se mueve después de emitir: el costo se congela
             * contra el movimiento que la emisión acaba de escribir.
             */
            $this->repository->updateStatus($model, $command);

            if ($command->status === 'confirmed') {
                $this->posting->post($model);
            }

            /** Un borrador anulado no revierte nada: nunca movió mercancía. */
            if ($command->status === 'cancelled' && $wasPosted) {
                $this->posting->reverse($model);
            }
        });

        return $this->repository->findOrFail($id, $companyId);
    }
}
