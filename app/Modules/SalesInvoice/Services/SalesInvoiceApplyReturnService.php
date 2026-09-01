<?php

declare(strict_types=1);

namespace App\Modules\SalesInvoice\Services;

use App\Modules\SalesInvoice\Commands\ApplySalesInvoiceReturnCommand;
use App\Modules\SalesInvoice\Commands\WriteSalesInvoiceLineReturnCommand;
use App\Modules\SalesInvoice\Exceptions\SalesInvoiceLineNotFoundException;
use App\Modules\SalesInvoice\Exceptions\SalesInvoiceOverReturnedException;
use App\Modules\SalesInvoice\Models\SalesInvoiceLine;
use App\Modules\SalesInvoice\Repositories\Contracts\SalesInvoiceRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * El único camino que mueve `returned_quantity` de una línea de factura de
 * venta.
 *
 * Lo llaman las devoluciones de venta al confirmarse, y otra vez con el signo
 * contrario cuando esa devolución se anula. Lo devuelto no puede pasarse de lo
 * facturado ni bajar de cero.
 *
 * La transacción es anidable: llamado desde el documento que devuelve se suma a
 * la transacción abierta como savepoint.
 */
class SalesInvoiceApplyReturnService
{
    public function __construct(
        private readonly SalesInvoiceRepositoryInterface $repository,
    ) {}

    public function execute(ApplySalesInvoiceReturnCommand $command): SalesInvoiceLine
    {
        return DB::transaction(function () use ($command): SalesInvoiceLine {
            $line = $this->repository->lockLineById($command->salesInvoiceLineId, $command->companyId);

            if ($line === null) {
                throw new SalesInvoiceLineNotFoundException;
            }

            $returned = round((float) $line->returned_quantity + $command->returnedDelta, 4);

            if ($returned < 0 || $returned > round((float) $line->quantity, 4)) {
                throw new SalesInvoiceOverReturnedException;
            }

            return $this->repository->writeLineReturn(
                $line,
                new WriteSalesInvoiceLineReturnCommand(returnedQuantity: $returned),
            );
        });
    }
}
