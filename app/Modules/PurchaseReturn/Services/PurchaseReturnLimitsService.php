<?php

declare(strict_types=1);

namespace App\Modules\PurchaseReturn\Services;

use App\Modules\PurchaseInvoice\Models\PurchaseInvoice;
use App\Modules\PurchaseInvoice\Models\PurchaseInvoiceLine;
use App\Modules\PurchaseInvoice\Repositories\Contracts\PurchaseInvoiceRepositoryInterface;
use App\Modules\PurchaseReturn\Commands\PurchaseReturnLineData;
use App\Modules\PurchaseReturn\Repositories\Contracts\PurchaseReturnRepositoryInterface;
use Illuminate\Validation\ValidationException;

/**
 * Lo que la devolución no puede comprobar sin leer la factura: que la factura
 * sea suya y que no se devuelva más de lo que se compró.
 *
 * El lote y la serie ya no se comprueban aquí porque la línea no los captura:
 * los pide el despacho que la devolución genera, y es él quien los exige al
 * confirmarse.
 *
 * Vive aparte de los servicios de acción porque Crear y Actualizar la necesitan
 * igual: la devolución se edita en borrador y cada guardado vuelve a comprobar
 * lo mismo.
 */
class PurchaseReturnLimitsService
{
    public function __construct(
        private readonly PurchaseInvoiceRepositoryInterface $invoices,
        private readonly PurchaseReturnRepositoryInterface $returns,
    ) {}

    /**
     * @param  array<int, PurchaseReturnLineData>  $lines
     * @param  string|null  $returnId  La devolución que se está guardando: sus
     *                                 propias líneas ya guardadas no compiten
     *                                 consigo misma.
     *
     * @throws ValidationException
     */
    public function guard(
        ?string $invoiceId,
        string $supplierId,
        ?string $companyId,
        array $lines,
        ?string $returnId = null,
    ): void {
        if (blank($invoiceId)) {
            return;
        }

        $invoice = $this->invoices->findById($invoiceId, $companyId);

        if (! $invoice instanceof PurchaseInvoice) {
            throw ValidationException::withMessages([
                'purchase_invoice_id' => 'La factura indicada no existe en esta empresa.',
            ]);
        }

        if ($invoice->supplier_id !== $supplierId) {
            throw ValidationException::withMessages([
                'purchase_invoice_id' => 'La factura pertenece a otro proveedor.',
            ]);
        }

        /** Una factura anulada no compró nada: no hay qué devolverle. */
        if ($invoice->status === 'cancelled') {
            throw ValidationException::withMessages([
                'purchase_invoice_id' => 'No se puede devolver mercancía de una factura anulada.',
            ]);
        }

        $this->guardReturnedQuantities($invoice, $lines, $returnId);
    }

    /**
     * La cantidad devuelta no puede superar la facturada menos la ya devuelta
     * por otras devoluciones.
     *
     * @param  array<int, PurchaseReturnLineData>  $lines
     *
     * @throws ValidationException
     */
    private function guardReturnedQuantities(PurchaseInvoice $invoice, array $lines, ?string $returnId): void
    {
        $invoiceLines = $invoice->lines->keyBy('id');
        $errors = [];
        $requested = [];

        foreach ($lines as $index => $line) {
            if ($line->status !== 'active' || blank($line->purchaseInvoiceLineId)) {
                continue;
            }

            $invoiceLine = $invoiceLines->get($line->purchaseInvoiceLineId);

            if (! $invoiceLine instanceof PurchaseInvoiceLine) {
                $errors["lines.{$index}.purchase_invoice_line_id"] = 'Esa línea no pertenece a la factura elegida.';

                continue;
            }

            /** Varias líneas de la devolución pueden salir de la misma línea de factura. */
            $requested[$line->purchaseInvoiceLineId] ??= ['quantity' => 0.0, 'index' => $index];
            $requested[$line->purchaseInvoiceLineId]['quantity'] += $line->quantity;
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        if ($requested === []) {
            return;
        }

        $returned = $this->returns->returnedQuantities(array_keys($requested), $returnId);

        foreach ($requested as $invoiceLineId => $entry) {
            $invoiced = (float) $invoiceLines->get($invoiceLineId)->quantity;
            $available = round($invoiced - ($returned[$invoiceLineId] ?? 0.0), 4);

            if (round($entry['quantity'], 4) > $available) {
                $errors["lines.{$entry['index']}.quantity"] = $available > 0
                    ? "De esa línea solo quedan {$available} por devolver."
                    : 'Esa línea de la factura ya se devolvió por completo.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
