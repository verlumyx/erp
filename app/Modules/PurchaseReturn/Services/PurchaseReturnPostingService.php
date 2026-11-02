<?php

declare(strict_types=1);

namespace App\Modules\PurchaseReturn\Services;

use App\Modules\PurchaseInvoice\Commands\ApplyPurchaseInvoiceReturnCommand;
use App\Modules\PurchaseInvoice\Services\PurchaseInvoiceApplyReturnService;
use App\Modules\PurchaseOrder\Commands\ApplyPurchaseOrderReceiptCommand;
use App\Modules\PurchaseOrder\Models\PurchaseOrderLine;
use App\Modules\PurchaseOrder\Services\PurchaseOrderApplyReceiptService;
use App\Modules\PurchaseReturn\Models\PurchaseReturn;
use App\Modules\PurchaseReturn\Models\PurchaseReturnLine;
use App\Modules\PurchaseReturn\Repositories\Contracts\PurchaseReturnRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * El momento en que la devolución deja de ser un papel y consume el cupo de la
 * factura que la origina.
 *
 * Confirmarla apunta lo devuelto en cada línea facturada, que es lo que habilita
 * la nota de crédito; anularla libera ese cupo. Mientras está en borrador sus
 * líneas ya están escritas, pero nada se ha consumido.
 *
 * **No mueve inventario.** La devolución es el acuerdo con el proveedor, no la
 * salida de la mercancía: esa la asienta el Despacho que la lleva de vuelta.
 */
class PurchaseReturnPostingService
{
    public function __construct(
        private readonly PurchaseReturnRepositoryInterface $repository,
        private readonly PurchaseInvoiceApplyReturnService $applyToInvoiceLine,
        private readonly PurchaseOrderApplyReceiptService $applyToOrderLine,
    ) {}

    /** Apunta lo devuelto en la factura de origen y, si es por reposición, reabre la orden de compra. */
    public function post(PurchaseReturn $return): void
    {
        DB::transaction(function () use ($return): void {
            foreach ($this->repository->activeLines($return) as $line) {
                $qty = round((float) $line->quantity, 4);
                $this->moveInvoiceLine($return, $line, $qty);
                $this->moveOrderLine($return, $line, -$qty);
            }
        });
    }

    /** Deshace lo apuntado: la factura recupera el cupo devuelto y la orden cancela lo reabierto. */
    public function reverse(PurchaseReturn $return): void
    {
        DB::transaction(function () use ($return): void {
            foreach ($this->repository->activeLines($return) as $line) {
                $qty = round((float) $line->quantity, 4);
                $this->moveInvoiceLine($return, $line, -$qty);
                $this->moveOrderLine($return, $line, $qty);
            }
        });
    }

    /**
     * Apunta —o libera— lo devuelto en la línea de la factura de origen. Una
     * devolución sin factura no tiene dónde apuntarlo.
     */
    private function moveInvoiceLine(PurchaseReturn $return, PurchaseReturnLine $line, float $delta): void
    {
        if (blank($line->purchase_invoice_line_id) || $delta === 0.0) {
            return;
        }

        $this->applyToInvoiceLine->execute(new ApplyPurchaseInvoiceReturnCommand(
            companyId: $return->company_id,
            purchaseInvoiceLineId: $line->purchase_invoice_line_id,
            returnedDelta: $delta,
        ));
    }

    /**
     * Reabre —o vuelve a cerrar— la línea de la orden de compra si la devolución
     * es por reposición.
     */
    private function moveOrderLine(PurchaseReturn $return, PurchaseReturnLine $line, float $delta): void
    {
        if (! $return->isReplacement() || $delta === 0.0) {
            return;
        }

        $invoiceLine = $line->purchaseInvoiceLine;

        if (
            $invoiceLine === null
            || $invoiceLine->sourceable_type !== PurchaseOrderLine::MORPH_ALIAS
            || blank($invoiceLine->sourceable_id)
        ) {
            return;
        }

        $this->applyToOrderLine->execute(new ApplyPurchaseOrderReceiptCommand(
            companyId: $return->company_id,
            purchaseOrderLineId: $invoiceLine->sourceable_id,
            receivedDelta: $delta,
        ));
    }
}
