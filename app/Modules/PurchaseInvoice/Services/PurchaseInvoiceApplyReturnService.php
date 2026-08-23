<?php

declare(strict_types=1);

namespace App\Modules\PurchaseInvoice\Services;

use App\Modules\PurchaseInvoice\Commands\ApplyPurchaseInvoiceReturnCommand;
use App\Modules\PurchaseInvoice\Commands\WritePurchaseInvoiceLineReturnCommand;
use App\Modules\PurchaseInvoice\Exceptions\PurchaseInvoiceLineNotFoundException;
use App\Modules\PurchaseInvoice\Exceptions\PurchaseInvoiceOverReturnedException;
use App\Modules\PurchaseInvoice\Models\PurchaseInvoiceLine;
use App\Modules\PurchaseInvoice\Repositories\Contracts\PurchaseInvoiceRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * El único camino que mueve `returned_quantity` de una línea de factura de
 * compra.
 *
 * Lo llaman las devoluciones de compra al confirmarse, y otra vez con el signo
 * contrario cuando esa devolución se anula. Lo devuelto no puede pasarse de lo
 * facturado ni bajar de cero.
 *
 * La transacción es anidable: llamado desde el documento que devuelve se suma a
 * la transacción abierta como savepoint.
 */
class PurchaseInvoiceApplyReturnService
{
    public function __construct(
        private readonly PurchaseInvoiceRepositoryInterface $repository,
    ) {}

    public function execute(ApplyPurchaseInvoiceReturnCommand $command): PurchaseInvoiceLine
    {
        return DB::transaction(function () use ($command): PurchaseInvoiceLine {
            $line = $this->repository->lockLineById($command->purchaseInvoiceLineId, $command->companyId);

            if ($line === null) {
                throw new PurchaseInvoiceLineNotFoundException;
            }

            $returned = round((float) $line->returned_quantity + $command->returnedDelta, 4);

            if ($returned < 0 || $returned > round((float) $line->quantity, 4)) {
                throw new PurchaseInvoiceOverReturnedException;
            }

            return $this->repository->writeLineReturn(
                $line,
                new WritePurchaseInvoiceLineReturnCommand(returnedQuantity: $returned),
            );
        });
    }
}
