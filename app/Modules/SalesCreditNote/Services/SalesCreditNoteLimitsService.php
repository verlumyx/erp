<?php

declare(strict_types=1);

namespace App\Modules\SalesCreditNote\Services;

use App\Modules\SalesCreditNote\Commands\SalesCreditNoteLineData;
use App\Modules\SalesCreditNote\Repositories\Contracts\SalesCreditNoteRepositoryInterface;
use App\Modules\SalesInvoice\Models\SalesInvoice;
use App\Modules\SalesInvoice\Repositories\Contracts\SalesInvoiceRepositoryInterface;
use Illuminate\Validation\ValidationException;

/**
 * Lo que la nota de crédito no puede comprobar sin leer la factura: que la
 * factura sea de ese cliente, que no acredite más de lo que se facturó y que el
 * importe no supere lo que la factura vale.
 *
 * Vive aparte de los servicios de acción porque Crear y Actualizar la necesitan
 * igual: la nota se edita en borrador y cada guardado vuelve a comprobar lo
 * mismo.
 */
class SalesCreditNoteLimitsService
{
    public function __construct(
        private readonly SalesInvoiceRepositoryInterface $invoices,
        private readonly SalesCreditNoteRepositoryInterface $notes,
    ) {}

    /**
     * @param  array<int, SalesCreditNoteLineData>  $lines
     * @param  string|null  $noteId  La nota que se está guardando: sus propias
     *                               líneas ya guardadas no compiten consigo misma.
     *
     * @throws ValidationException
     */
    public function guard(
        ?string $invoiceId,
        string $clientId,
        ?string $companyId,
        array $lines,
        ?string $noteId = null,
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

        /** Una factura anulada ya no debe nada: no hay qué acreditarle. */
        if ($invoice->status === 'cancelled') {
            throw ValidationException::withMessages([
                'sales_invoice_id' => 'No se puede acreditar una factura anulada.',
            ]);
        }

        $this->guardCreditedQuantities($invoice, $lines, $noteId);
        $this->guardCreditedAmount($invoice, $lines, $noteId);
    }

    /**
     * La cantidad acreditada no puede superar la facturada menos la ya
     * acreditada por otras notas.
     *
     * @param  array<int, SalesCreditNoteLineData>  $lines
     *
     * @throws ValidationException
     */
    private function guardCreditedQuantities(SalesInvoice $invoice, array $lines, ?string $noteId): void
    {
        $invoiceLines = $invoice->lines->keyBy('id');
        $errors = [];
        $requested = [];

        foreach ($lines as $index => $line) {
            if ($line->status !== 'active' || blank($line->salesInvoiceLineId)) {
                continue;
            }

            if (! $invoiceLines->has($line->salesInvoiceLineId)) {
                $errors["lines.{$index}.sales_invoice_line_id"] = 'Esa línea no pertenece a la factura elegida.';

                continue;
            }

            /** Varias líneas de la nota pueden acreditar la misma línea de factura. */
            $requested[$line->salesInvoiceLineId] ??= ['quantity' => 0.0, 'index' => $index];
            $requested[$line->salesInvoiceLineId]['quantity'] += $line->quantity;
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

    /**
     * El importe de la nota no puede superar lo que vale la factura —el saldo
     * pendiente más lo ya cobrado—, descontando lo que otras notas vivas ya le
     * acreditaron. Se acredita una venta, no se regala dinero sobre ella.
     *
     * @param  array<int, SalesCreditNoteLineData>  $lines
     *
     * @throws ValidationException
     */
    private function guardCreditedAmount(SalesInvoice $invoice, array $lines, ?string $noteId): void
    {
        $requested = round(array_sum(array_map(
            static fn (SalesCreditNoteLineData $line): float => $line->status === 'active' ? $line->total : 0.0,
            $lines,
        )), 2);

        $invoiced = round((float) $invoice->balance + (float) $invoice->paid_amount, 2);
        $available = round($invoiced - $this->notes->creditedAmount($invoice->id, $noteId), 2);

        if ($requested > $available) {
            throw ValidationException::withMessages([
                'sales_invoice_id' => $available > 0
                    ? "De esa factura solo quedan {$available} por acreditar."
                    : 'Esa factura ya está acreditada por completo.',
            ]);
        }
    }
}
