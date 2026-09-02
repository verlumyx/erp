<?php

declare(strict_types=1);

namespace App\Modules\PurchaseCreditNote\Repositories;

use App\Modules\ExchangeRate\Commands\DocumentRatesData;
use App\Modules\Item\Models\Item;
use App\Modules\Item\Models\ItemUnit;
use App\Modules\Item\Repositories\Contracts\ItemRepositoryInterface;
use App\Modules\PurchaseCreditNote\Commands\CreatePurchaseCreditNoteCommand;
use App\Modules\PurchaseCreditNote\Commands\PurchaseCreditNoteLineData;
use App\Modules\PurchaseCreditNote\Commands\SearchPurchaseCreditNoteCommand;
use App\Modules\PurchaseCreditNote\Commands\UpdatePurchaseCreditNoteCommand;
use App\Modules\PurchaseCreditNote\Commands\UpdateStatusPurchaseCreditNoteCommand;
use App\Modules\PurchaseCreditNote\Commands\WritePurchaseCreditNoteAppliedCommand;
use App\Modules\PurchaseCreditNote\Models\PurchaseCreditNote;
use App\Modules\PurchaseCreditNote\Models\PurchaseCreditNoteLine;
use App\Modules\PurchaseCreditNote\Repositories\Contracts\PurchaseCreditNoteRepositoryInterface;
use Illuminate\Support\Facades\DB;

class PurchaseCreditNoteRepository extends PurchaseCreditNoteFilters implements PurchaseCreditNoteRepositoryInterface
{
    public function __construct(
        private readonly ItemRepositoryInterface $items,
    ) {}

    public function create(CreatePurchaseCreditNoteCommand $command, DocumentRatesData $rates): void
    {
        DB::transaction(function () use ($command, $rates): void {
            $note = PurchaseCreditNote::create([
                'id' => $command->id,
                'company_id' => $command->companyId,
                'code' => $this->generateNextCode($command->companyId),
                'supplier_id' => $command->supplierId,
                'purchase_invoice_id' => $command->purchaseInvoiceId,
                'purchase_return_id' => $command->purchaseReturnId,
                'supplier_document_number' => $command->supplierDocumentNumber,
                'note_date' => $command->noteDate,
                'reason' => $command->reason,
                'reason_detail' => $command->reasonDetail,
                'affects_inventory' => $command->affectsInventory,
                ...$rates->toAttributes(),
                ...$this->totals($command->lines, $rates),
                /** El crédito nace entero: lo consumen las aplicaciones a facturas. */
                'applied_amount' => 0,
                'notes' => $command->notes,
                'status' => 'draft',
                'created_by' => $command->createdBy,
            ]);

            $this->syncLines($note, $command->lines);
        });
    }

    public function findById(string $id, ?string $companyId = null): ?PurchaseCreditNote
    {
        return PurchaseCreditNote::query()
            ->with($this->detailRelations())
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->find($id);
    }

    public function findOrFail(string $id, ?string $companyId = null): PurchaseCreditNote
    {
        return PurchaseCreditNote::query()
            ->with($this->detailRelations())
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->findOrFail($id);
    }

    public function update(
        PurchaseCreditNote $model,
        UpdatePurchaseCreditNoteCommand $command,
        DocumentRatesData $rates,
    ): void {
        DB::transaction(function () use ($model, $command, $rates): void {
            /** Lo ya aplicado a facturas y la marca de anulación no se editan aquí. */
            $model->update([
                'supplier_id' => $command->supplierId,
                'purchase_invoice_id' => $command->purchaseInvoiceId,
                'purchase_return_id' => $command->purchaseReturnId,
                'supplier_document_number' => $command->supplierDocumentNumber,
                'note_date' => $command->noteDate,
                'reason' => $command->reason,
                'reason_detail' => $command->reasonDetail,
                'affects_inventory' => $command->affectsInventory,
                ...$rates->toAttributes(),
                ...$this->totals($command->lines, $rates, (float) $model->applied_amount),
                'notes' => $command->notes,
            ]);

            $this->syncLines($model, $command->lines);
        });
    }

    /**
     * @return array<int, PurchaseCreditNoteLine>
     */
    public function activeLines(PurchaseCreditNote $model): array
    {
        return PurchaseCreditNoteLine::query()
            ->with(['item', 'purchaseInvoiceLine'])
            ->where('purchase_credit_note_id', $model->id)
            ->where('status', 'active')
            ->orderBy('line_number')
            ->get()
            ->all();
    }

    public function updateStatus(PurchaseCreditNote $model, UpdateStatusPurchaseCreditNoteCommand $command): void
    {
        $attributes = ['status' => $command->status];

        if ($command->status === 'cancelled') {
            $attributes['cancelled_at'] = now();
        }

        $model->update($attributes);
    }

    public function lockById(string $id, ?string $companyId = null): ?PurchaseCreditNote
    {
        return PurchaseCreditNote::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->lockForUpdate()
            ->find($id);
    }

    public function writeApplied(
        PurchaseCreditNote $model,
        WritePurchaseCreditNoteAppliedCommand $command,
    ): PurchaseCreditNote {
        $model->update([
            'applied_amount' => $command->appliedAmount,
            'balance' => $command->balance,
        ]);

        return $model;
    }

    /**
     * @return array{ data: PurchaseCreditNote[], total: int }
     */
    public function search(SearchPurchaseCreditNoteCommand $command): array
    {
        $query = PurchaseCreditNote::query()
            ->with(['supplier', 'purchaseInvoice', 'purchaseReturn'])
            ->when($command->companyId, fn ($q) => $q->where('company_id', $command->companyId));

        $query = $this->apply($query, $command->filters);

        $total = $query->count();

        $data = $query->orderByDesc('note_date')
            ->orderByDesc('code')
            ->limit($command->limit)
            ->offset($command->offset)
            ->get();

        return ['data' => $data->all(), 'total' => $total];
    }

    /**
     * Cantidad ya acreditada de cada línea de factura.
     *
     * Suma solo las líneas activas de notas que siguen vivas: una nota anulada
     * devuelve su cupo, y la nota que se está guardando no compite consigo
     * misma.
     *
     * @param  array<int, string>  $invoiceLineIds
     * @return array<string, float>
     */
    public function creditedQuantities(array $invoiceLineIds, ?string $exceptNoteId = null): array
    {
        if ($invoiceLineIds === []) {
            return [];
        }

        return PurchaseCreditNoteLine::query()
            ->whereIn('purchase_invoice_line_id', $invoiceLineIds)
            ->where('app_purchase_credit_note_lines.status', 'active')
            ->when($exceptNoteId, fn ($q) => $q->where('purchase_credit_note_id', '!=', $exceptNoteId))
            ->whereHas('purchaseCreditNote', fn ($q) => $q->where('status', '!=', 'cancelled'))
            ->groupBy('purchase_invoice_line_id')
            ->selectRaw('purchase_invoice_line_id, SUM(quantity) as credited')
            ->pluck('credited', 'purchase_invoice_line_id')
            ->map(fn ($credited): float => (float) $credited)
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function detailRelations(): array
    {
        return ['supplier', 'purchaseInvoice', 'purchaseReturn', 'lines.item', 'lines.measurementUnit'];
    }

    /**
     * Alinea `app_purchase_credit_note_lines` con lo enviado desde la pantalla.
     *
     * Las filas existentes se reconocen por su `id` y conservan su
     * `line_number`; las nuevas toman el siguiente número libre. Las que dejan
     * de venir no se borran, se desactivan (política de no borrado), y por eso
     * los números no se recalculan: el par `(nota, line_number)` es único y una
     * fila inactiva sigue ocupando el suyo.
     *
     * @param  array<int, PurchaseCreditNoteLineData>  $lines
     */
    private function syncLines(PurchaseCreditNote $note, array $lines): void
    {
        $existing = PurchaseCreditNoteLine::query()
            ->where('purchase_credit_note_id', $note->id)
            ->get()
            ->keyBy('id');

        $factors = $this->conversionFactors($note->company_id, $lines);
        $nextNumber = (int) $existing->max('line_number');
        $keep = [];

        foreach ($lines as $line) {
            $current = $line->id !== null ? $existing->get($line->id) : null;

            $factor = $factors[$line->itemId.'|'.$line->measurementUnitId] ?? 1.0;

            $attributes = [
                'company_id' => $note->company_id,
                'item_id' => $line->itemId,
                'measurement_unit_id' => $line->measurementUnitId,
                'purchase_invoice_line_id' => $line->purchaseInvoiceLineId,
                'warehouse_id' => $line->warehouseId,
                'lot_id' => $line->lotId,
                'quantity' => $line->quantity,
                'base_quantity' => round($line->quantity * $factor, 4),
                'unit_price' => $line->unitPrice,
                'discount_percent' => $line->discountPercent,
                'discount_amount' => $line->discountAmount,
                'tax_id' => $line->taxId,
                'tax_percent' => $line->taxPercent,
                'tax_amount' => $line->taxAmount,
                'withholding_percent' => $line->withholdingPercent,
                'withholding_amount' => $line->withholdingAmount,
                'subtotal' => $line->subtotal,
                'total' => $line->total,
                'status' => $line->status,
                'notes' => $line->notes,
            ];

            if ($current !== null) {
                $current->update($attributes);
                $keep[] = $current->id;

                continue;
            }

            $keep[] = PurchaseCreditNoteLine::create([
                ...$attributes,
                'purchase_credit_note_id' => $note->id,
                'line_number' => ++$nextNumber,
            ])->id;
        }

        PurchaseCreditNoteLine::query()
            ->where('purchase_credit_note_id', $note->id)
            ->when($keep !== [], fn ($q) => $q->whereNotIn('id', $keep))
            ->update(['status' => 'inactive']);
    }

    /**
     * Factor de conversión a la unidad base por par artículo/unidad.
     *
     * Se resuelve a través del repositorio de artículos: el módulo de notas de
     * crédito nunca consulta las tablas del módulo de inventario directamente.
     * Un par sin unidad registrada cae en 1, que es lo que valida el Request.
     *
     * @param  array<int, PurchaseCreditNoteLineData>  $lines
     * @return array<string, float>
     */
    private function conversionFactors(?string $companyId, array $lines): array
    {
        $factors = [];

        foreach (array_unique(array_map(fn (PurchaseCreditNoteLineData $line): string => $line->itemId, $lines)) as $itemId) {
            $item = $this->items->findById($itemId, $companyId);

            if (! $item instanceof Item) {
                continue;
            }

            foreach ($item->units as $unit) {
                /** @var ItemUnit $unit */
                $factors[$itemId.'|'.$unit->measurement_unit_id] = (float) $unit->conversion_factor;
            }
        }

        return $factors;
    }

    /**
     * Totales de la cabecera. Suman **solo** las líneas activas.
     *
     * La nota no lleva descuento global ni gastos: lo que acredita es lo que
     * suman sus líneas. `balance` es el crédito que queda disponible después de
     * lo ya aplicado a facturas.
     *
     * @param  array<int, PurchaseCreditNoteLineData>  $lines
     * @return array<string, float>
     */
    private function totals(array $lines, DocumentRatesData $rates, float $appliedAmount = 0): array
    {
        $active = array_filter($lines, fn (PurchaseCreditNoteLineData $line): bool => $line->status === 'active');

        $subtotal = round(array_sum(array_map(fn (PurchaseCreditNoteLineData $line): float => $line->subtotal, $active)), 2);
        $tax = round(array_sum(array_map(fn (PurchaseCreditNoteLineData $line): float => $line->taxAmount, $active)), 2);
        $total = round($subtotal + $tax, 2);

        return [
            'subtotal' => $subtotal,
            'tax_amount' => $tax,
            'total' => $total,
            /** La nota es un documento fiscal: su valor en bolívares queda escrito. */
            'subtotal_ves' => $this->inLocalCurrency($subtotal, $rates),
            'tax_amount_ves' => $this->inLocalCurrency($tax, $rates),
            'total_ves' => $this->inLocalCurrency($total, $rates),
            'balance' => round($total - $appliedAmount, 2),
        ];
    }

    /** Importe en bolívares con los decimales que la empresa usa para importes. */
    private function inLocalCurrency(float $amount, DocumentRatesData $rates): float
    {
        return round($amount * $rates->exchangeRate, $rates->amountDecimals);
    }

    /**
     * Generate the next sequential per-company code (NCP000001, NCP000002, …).
     */
    private function generateNextCode(string $companyId): string
    {
        $last = PurchaseCreditNote::query()
            ->where('company_id', $companyId)
            ->where('code', 'like', PurchaseCreditNote::CODE_PREFIX.'%')
            ->lockForUpdate()
            ->orderByDesc('code')
            ->value('code');

        $next = $last !== null
            ? ((int) substr($last, strlen(PurchaseCreditNote::CODE_PREFIX))) + 1
            : 1;

        return PurchaseCreditNote::CODE_PREFIX.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
