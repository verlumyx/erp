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
     * es justo el momento en que quedan congeladas. Lo único que la emisión
     * añade es el costo de la mercancía vendida, leído del despacho que la
     * sacó.
     *
     * Anular no deshace movimientos de inventario: la factura nunca escribió
     * ninguno. Para devolver la mercancía a la bodega se anula el despacho.
     */
    public function execute(string $id, UpdateStatusSalesInvoiceCommand $command, ?string $companyId = null): SalesInvoice
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new SalesInvoiceNotFoundException;
        }

        DB::transaction(function () use ($model, $command): void {
            /**
             * El costo se congela después de emitir: hasta que la factura no
             * está confirmada, sus líneas no tienen por qué llevarlo escrito.
             */
            $this->repository->updateStatus($model, $command);

            if ($command->status === 'confirmed') {
                $this->posting->post($model);
            }
        });

        return $this->repository->findOrFail($id, $companyId);
    }
}
