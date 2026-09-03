<?php

declare(strict_types=1);

namespace App\Modules\PurchaseInvoice\Services;

use App\Modules\PurchaseInvoice\Models\PurchaseInvoice;
use App\Modules\PurchaseInvoice\Models\PurchaseInvoiceLine;
use App\Modules\PurchaseInvoice\Repositories\Contracts\PurchaseInvoiceRepositoryInterface;
use App\Modules\PurchaseOrder\Commands\ApplyPurchaseOrderInvoicedCommand;
use App\Modules\PurchaseOrder\Models\PurchaseOrderLine;
use App\Modules\PurchaseOrder\Services\PurchaseOrderApplyInvoicedService;
use App\Modules\Supplier\Commands\ApplySupplierBalanceCommand;
use App\Modules\Supplier\Services\SupplierApplyBalanceService;
use Illuminate\Support\Facades\DB;

/**
 * El momento en que la factura de compra deja de ser un papel y se convierte en
 * deuda con el proveedor.
 *
 * Confirmarla carga su cuenta por pagar y apunta lo facturado en la orden que la
 * originó. Anularla hace exactamente lo contrario. Mientras está en borrador sus
 * líneas ya están escritas, pero ningún saldo se ha movido.
 *
 * **No toca el inventario.** La mercancía entra con su Entrada, antes o después
 * de que llegue la factura, y es esa Entrada la que escribe el kardex y recalcula
 * el costo promedio. El `landed_cost` que la factura guarda por línea es el costo
 * que el documento declara, no una orden de valorar existencia.
 *
 * Lo que se le debe al proveedor no es el total de la factura sino
 * `total - withholding_amount`: la retención es una parte del impuesto que se
 * entera al fisco en vez de pagarse al proveedor, así que baja lo que se le
 * paga, no lo que la factura vale.
 */
class PurchaseInvoicePostingService
{
    public function __construct(
        private readonly PurchaseInvoiceRepositoryInterface $repository,
        private readonly SupplierApplyBalanceService $applyToSupplier,
        private readonly PurchaseOrderApplyInvoicedService $applyToOrderLine,
    ) {}

    /** Emite la factura: genera la deuda y consume el saldo de la orden. */
    public function post(PurchaseInvoice $invoice): void
    {
        DB::transaction(function () use ($invoice): void {
            foreach ($this->repository->activeLines($invoice) as $line) {
                $this->moveOrderLine($invoice, $line, round((float) $line->quantity, 4));
            }

            $this->moveSupplierBalance($invoice, $this->payableAmount($invoice));
        });
    }

    /**
     * Deshace la emisión: el proveedor deja de tener esa cuenta por cobrarnos y
     * la orden recupera lo que había dado por facturado.
     */
    public function reverse(PurchaseInvoice $invoice): void
    {
        DB::transaction(function () use ($invoice): void {
            foreach ($this->repository->activeLines($invoice) as $line) {
                $this->moveOrderLine($invoice, $line, -round((float) $line->quantity, 4));
            }

            $this->moveSupplierBalance($invoice, -$this->payableAmount($invoice));
        });
    }

    /**
     * Apunta —o libera— lo facturado en la línea de la orden de origen. Una
     * factura directa, sin orden previa, no tiene dónde apuntarlo.
     */
    private function moveOrderLine(PurchaseInvoice $invoice, PurchaseInvoiceLine $line, float $delta): void
    {
        if ($line->sourceable_type !== PurchaseOrderLine::MORPH_ALIAS) {
            return;
        }

        if (blank($line->sourceable_id) || $delta === 0.0) {
            return;
        }

        $this->applyToOrderLine->execute(new ApplyPurchaseOrderInvoicedCommand(
            companyId: $invoice->company_id,
            purchaseOrderLineId: $line->sourceable_id,
            invoicedDelta: $delta,
        ));
    }

    /** Lo que se le debe al proveedor por esta factura. */
    private function payableAmount(PurchaseInvoice $invoice): float
    {
        return round((float) $invoice->total - (float) $invoice->withholding_amount, 2);
    }

    /** Carga (o descarga) la cuenta por pagar del proveedor. */
    private function moveSupplierBalance(PurchaseInvoice $invoice, float $delta): void
    {
        if ($delta === 0.0) {
            return;
        }

        $this->applyToSupplier->execute(new ApplySupplierBalanceCommand(
            companyId: $invoice->company_id,
            supplierId: $invoice->supplier_id,
            currentBalanceDelta: $delta,
        ));
    }
}
