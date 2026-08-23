<?php

declare(strict_types=1);

namespace App\Modules\PurchaseCreditNote\Services;

use App\Modules\PurchaseCreditNote\Commands\PurchaseCreditNoteLineData;
use App\Modules\PurchaseCreditNote\Repositories\Contracts\PurchaseCreditNoteRepositoryInterface;
use App\Modules\PurchaseInvoice\Models\PurchaseInvoice;
use App\Modules\PurchaseInvoice\Repositories\Contracts\PurchaseInvoiceRepositoryInterface;
use Illuminate\Validation\ValidationException;

/**
 * Lo que la nota de crédito no puede comprobar sin leer la factura: que la
 * factura sea suya y que no acredite más de lo que se facturó.
 *
 * Vive aparte de los servicios de acción porque Crear y Actualizar la necesitan
 * igual: la nota se edita en borrador y cada guardado vuelve a comprobar lo
 * mismo.
 */
class PurchaseCreditNoteLimitsService
{
    public function __construct(
        private readonly PurchaseInvoiceRepositoryInterface $invoices,
        private readonly PurchaseCreditNoteRepositoryInterface $notes,
    ) {}

    /**
     * @param  array<int, PurchaseCreditNoteLineData>  $lines
     * @param  string|null  $noteId  La nota que se está guardando: sus propias
     *                               líneas ya guardadas no compiten consigo misma.
     *
     * @throws ValidationException
     */
    public function guard(
        ?string $invoiceId,
        string $supplierId,
        ?string $companyId,
        array $lines,
        ?string $noteId = null,
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

        /** Una factura anulada ya no debe nada: no hay qué acreditarle. */
        if ($invoice->status === 'cancelled') {
            throw ValidationException::withMessages([
                'purchase_invoice_id' => 'No se puede acreditar una factura anulada.',
            ]);
        }

        $this->guardCreditedQuantities($invoice, $lines, $noteId);
    }

    /**
     * La cantidad acreditada no puede superar la facturada menos la ya
     * acreditada por otras notas.
     *
     * @param  array<int, PurchaseCreditNoteLineData>  $lines
     *
     * @throws ValidationException
     */
    private function guardCreditedQuantities(PurchaseInvoice $invoice, array $lines, ?string $noteId): void
    {
        $invoiceLines = $invoice->lines->keyBy('id');
        $errors = [];
        $requested = [];

        foreach ($lines as $index => $line) {
            if ($line->status !== 'active' || blank($line->purchaseInvoiceLineId)) {
                continue;
            }

            if (! $invoiceLines->has($line->purchaseInvoiceLineId)) {
                $errors["lines.{$index}.purchase_invoice_line_id"] = 'Esa línea no pertenece a la factura elegida.';

                continue;
            }

            /** Varias líneas de la nota pueden acreditar la misma línea de factura. */
            $requested[$line->purchaseInvoiceLineId] ??= ['quantity' => 0.0, 'index' => $index];
            $requested[$line->purchaseInvoiceLineId]['quantity'] += $line->quantity;
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        if ($requested === []) {
            return;
        }

        $credited = $this->notes->creditedQuantities(array_keys($requested), $noteId);

        foreach ($requested as $invoiceLineId => $entry) {
            $invoiced = (float) $invoiceLines->get($invoiceLineId)->quantity;
            $available = round($invoiced - ($credited[$invoiceLineId] ?? 0.0), 4);

            if (round($entry['quantity'], 4) > $available) {
                $errors["lines.{$entry['index']}.quantity"] = $available > 0
                    ? "De esa línea solo quedan {$available} por acreditar."
                    : 'Esa línea de la factura ya está acreditada por completo.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
