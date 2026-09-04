<?php

declare(strict_types=1);

namespace App\Modules\SalesReturn\Services;

use App\Modules\SalesInvoice\Models\SalesInvoice;
use App\Modules\SalesInvoice\Models\SalesInvoiceLine;
use App\Modules\SalesInvoice\Repositories\Contracts\SalesInvoiceRepositoryInterface;
use App\Modules\SalesReturn\Commands\SalesReturnLineData;
use App\Modules\SalesReturn\Repositories\Contracts\SalesReturnRepositoryInterface;
use Illuminate\Validation\ValidationException;

/**
 * Lo que la devolución no puede comprobar sin leer la factura: que la factura
 * sea suya y que no se devuelva más de lo que se vendió.
 *
 * El lote y la serie ya no se comprueban aquí porque la línea no los captura:
 * los pide la entrada que la devolución genera, y es ella quien los exige al
 * confirmarse.
 *
 * Vive aparte de los servicios de acción porque Crear y Actualizar la necesitan
 * igual: la devolución se edita en borrador y cada guardado vuelve a comprobar
 * lo mismo.
 */
class SalesReturnLimitsService
{
    public function __construct(
        private readonly SalesInvoiceRepositoryInterface $invoices,
        private readonly SalesReturnRepositoryInterface $returns,
    ) {}

    /**
     * @param  array<int, SalesReturnLineData>  $lines
     * @param  string|null  $returnId  La devolución que se está guardando: sus
     *                                 propias líneas ya guardadas no compiten
     *                                 consigo misma.
     *
     * @throws ValidationException
     */
    public function guard(
        ?string $invoiceId,
        string $clientId,
        ?string $companyId,
        array $lines,
        ?string $returnId = null,
    ): void {
        if (blank($invoiceId)) {
            return;
        }

        $invoice = $this->invoices->findById($invoiceId, $companyId);

        if (! $invoice instanceof SalesInvoice) {
            throw ValidationException::withMessages([
                'sales_invoice_id' => 'La factura indicada no existe en esta empresa.',
            ]);
        }

        if ($invoice->client_id !== $clientId) {
            throw ValidationException::withMessages([
                'sales_invoice_id' => 'La factura pertenece a otro cliente.',
            ]);
        }

        /** Una factura anulada no vendió nada: no hay qué devolver de ella. */
        if ($invoice->status === 'cancelled') {
            throw ValidationException::withMessages([
                'sales_invoice_id' => 'No se puede devolver mercancía de una factura anulada.',
            ]);
        }

        $this->guardReturnedQuantities($invoice, $lines, $returnId);
    }

    /**
     * La cantidad devuelta no puede superar la facturada menos la ya devuelta
     * por otras devoluciones.
     *
     * @param  array<int, SalesReturnLineData>  $lines
     *
     * @throws ValidationException
     */
    private function guardReturnedQuantities(SalesInvoice $invoice, array $lines, ?string $returnId): void
    {
        $invoiceLines = $invoice->lines->keyBy('id');
        $errors = [];
        $requested = [];

        foreach ($lines as $index => $line) {
            if ($line->status !== 'active' || blank($line->salesInvoiceLineId)) {
                continue;
            }

            $invoiceLine = $invoiceLines->get($line->salesInvoiceLineId);

            if (! $invoiceLine instanceof SalesInvoiceLine) {
                $errors["lines.{$index}.sales_invoice_line_id"] = 'Esa línea no pertenece a la factura elegida.';

                continue;
            }

            /** Varias líneas de la devolución pueden salir de la misma línea de factura. */
            $requested[$line->salesInvoiceLineId] ??= ['quantity' => 0.0, 'index' => $index];
            $requested[$line->salesInvoiceLineId]['quantity'] += $line->quantity;
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
