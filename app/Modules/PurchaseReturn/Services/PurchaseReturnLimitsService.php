<?php

declare(strict_types=1);

namespace App\Modules\PurchaseReturn\Services;

use App\Modules\Item\Models\Item;
use App\Modules\ItemSerial\Models\ItemSerial;
use App\Modules\PurchaseInvoice\Models\PurchaseInvoice;
use App\Modules\PurchaseInvoice\Models\PurchaseInvoiceLine;
use App\Modules\PurchaseInvoice\Repositories\Contracts\PurchaseInvoiceRepositoryInterface;
use App\Modules\PurchaseReturn\Commands\PurchaseReturnLineData;
use App\Modules\PurchaseReturn\Repositories\Contracts\PurchaseReturnRepositoryInterface;
use Illuminate\Validation\ValidationException;

/**
 * Lo que la devolución no puede comprobar sin leer la factura: que la factura
 * sea suya, que no se devuelva más de lo que se compró y que el lote o la serie
 * que vuelven sean los que llegaron.
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
        $this->guardSerials($lines, $companyId);

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
     * por otras devoluciones, y el lote que vuelve tiene que ser el que llegó.
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

            $lotError = $this->lotError($invoiceLine, $line);

            if ($lotError !== null) {
                $errors["lines.{$index}.lot_id"] = $lotError;
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

    /**
     * Si el artículo llegó con lote, se devuelve exactamente ese: un lote
     * distinto sería otra mercancía con la misma etiqueta.
     */
    private function lotError(PurchaseInvoiceLine $invoiceLine, PurchaseReturnLineData $line): ?string
    {
        if (blank($invoiceLine->lot_id)) {
            return null;
        }

        if (blank($line->lotId)) {
            return 'Ese artículo se recibió con lote: indica el que devuelves.';
        }

        return $line->lotId === $invoiceLine->lot_id
            ? null
            : 'Solo se puede devolver el lote que se recibió en esa línea.';
    }

    /**
     * La serie que vuelve tiene que ser una del artículo de la línea. Un
     * artículo serializado no se devuelve a granel: cada unidad es su serie, así
     * que la línea que lo mueve devuelve exactamente una.
     *
     * @param  array<int, PurchaseReturnLineData>  $lines
     *
     * @throws ValidationException
     */
    private function guardSerials(array $lines, ?string $companyId): void
    {
        $errors = [];

        $itemTypes = Item::query()
            ->whereIn('id', array_values(array_unique(array_map(
                static fn (PurchaseReturnLineData $line): string => $line->itemId,
                $lines,
            ))))
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->pluck('type', 'id')
            ->all();

        $serialItems = ItemSerial::query()
            ->whereIn('id', array_values(array_filter(array_map(
                static fn (PurchaseReturnLineData $line): ?string => $line->serialId,
                $lines,
            ))))
            ->pluck('item_id', 'id')
            ->all();

        foreach ($lines as $index => $line) {
            if ($line->status !== 'active') {
                continue;
            }

            $serialized = ($itemTypes[$line->itemId] ?? null) === ItemSerial::TRACKABLE_ITEM_TYPE;

            if ($serialized && blank($line->serialId)) {
                $errors["lines.{$index}.serial_id"] = 'Ese artículo se controla por serie: indica la que devuelves.';

                continue;
            }

            if (blank($line->serialId)) {
                continue;
            }

            if (($serialItems[$line->serialId] ?? null) !== $line->itemId) {
                $errors["lines.{$index}.serial_id"] = 'La serie indicada no es de ese artículo.';

                continue;
            }

            /** Una serie identifica una unidad: no se devuelven dos con la misma. */
            if (round($line->quantity, 4) !== 1.0) {
                $errors["lines.{$index}.quantity"] = 'Una serie devuelve exactamente una unidad.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
