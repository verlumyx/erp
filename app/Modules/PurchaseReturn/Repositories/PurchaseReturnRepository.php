<?php

declare(strict_types=1);

namespace App\Modules\PurchaseReturn\Repositories;

use App\Modules\ExchangeRate\Commands\DocumentRatesData;
use App\Modules\Item\Models\Item;
use App\Modules\Item\Models\ItemUnit;
use App\Modules\Item\Repositories\Contracts\ItemRepositoryInterface;
use App\Modules\PurchaseReturn\Commands\CreatePurchaseReturnCommand;
use App\Modules\PurchaseReturn\Commands\PurchaseReturnLineData;
use App\Modules\PurchaseReturn\Commands\SearchPurchaseReturnCommand;
use App\Modules\PurchaseReturn\Commands\UpdatePurchaseReturnCommand;
use App\Modules\PurchaseReturn\Commands\UpdateStatusPurchaseReturnCommand;
use App\Modules\PurchaseReturn\Commands\WritePurchaseReturnCreditNoteCommand;
use App\Modules\PurchaseReturn\Models\PurchaseReturn;
use App\Modules\PurchaseReturn\Models\PurchaseReturnLine;
use App\Modules\PurchaseReturn\Repositories\Contracts\PurchaseReturnRepositoryInterface;
use Illuminate\Support\Facades\DB;

class PurchaseReturnRepository extends PurchaseReturnFilters implements PurchaseReturnRepositoryInterface
{
    public function __construct(
        private readonly ItemRepositoryInterface $items,
    ) {}

    /**
     * Las líneas llegan ya valoradas por `PurchaseReturnPricingService`: el
     * repositorio escribe importes, nunca los decide.
     *
     * @param  array<int, PurchaseReturnLineData>  $lines
     */
    public function create(CreatePurchaseReturnCommand $command, DocumentRatesData $rates, array $lines): void
    {
        DB::transaction(function () use ($command, $rates, $lines): void {
            $return = PurchaseReturn::create([
                'id' => $command->id,
                'company_id' => $command->companyId,
                'code' => $this->generateNextCode($command->companyId),
                'supplier_id' => $command->supplierId,
                'purchase_invoice_id' => $command->purchaseInvoiceId,
                'entry_id' => $command->entryId,
                'warehouse_id' => $command->warehouseId,
                'return_date' => $command->returnDate,
                'reason' => $command->reason,
                'reason_detail' => $command->reasonDetail,
                ...$rates->toAttributes(),
                ...$this->totals($lines),
                'carrier' => $command->carrier,
                'tracking_number' => $command->trackingNumber,
                'notes' => $command->notes,
                'status' => 'draft',
                'created_by' => $command->createdBy,
            ]);

            $this->syncLines($return, $lines);
        });
    }

    public function findById(string $id, ?string $companyId = null): ?PurchaseReturn
    {
        return PurchaseReturn::query()
            ->with($this->detailRelations())
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->find($id);
    }

    public function findOrFail(string $id, ?string $companyId = null): PurchaseReturn
    {
        return PurchaseReturn::query()
            ->with($this->detailRelations())
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->findOrFail($id);
    }

    /**
     * @param  array<int, PurchaseReturnLineData>  $lines  Ya valoradas.
     */
    public function update(
        PurchaseReturn $model,
        UpdatePurchaseReturnCommand $command,
        DocumentRatesData $rates,
        array $lines,
    ): void {
        DB::transaction(function () use ($model, $command, $rates, $lines): void {
            /** La nota de crédito generada y la marca de anulación no se editan aquí. */
            $model->update([
                'supplier_id' => $command->supplierId,
                'purchase_invoice_id' => $command->purchaseInvoiceId,
                'entry_id' => $command->entryId,
                'warehouse_id' => $command->warehouseId,
                'return_date' => $command->returnDate,
                'reason' => $command->reason,
                'reason_detail' => $command->reasonDetail,
                ...$rates->toAttributes(),
                ...$this->totals($lines),
                'carrier' => $command->carrier,
                'tracking_number' => $command->trackingNumber,
                'notes' => $command->notes,
            ]);

            $this->syncLines($model, $lines);
        });
    }

    public function updateStatus(PurchaseReturn $model, UpdateStatusPurchaseReturnCommand $command): void
    {
        $attributes = ['status' => $command->status];

        if ($command->status === 'cancelled') {
            $attributes['cancelled_at'] = now();
        }

        $model->update($attributes);
    }

    public function lockById(string $id, ?string $companyId = null): ?PurchaseReturn
    {
        return PurchaseReturn::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->lockForUpdate()
            ->find($id);
    }

    public function writeCreditNote(
        PurchaseReturn $model,
        WritePurchaseReturnCreditNoteCommand $command,
    ): PurchaseReturn {
        $model->update([
            'credit_note_id' => $command->creditNoteId,
            'status' => $command->status,
        ]);

        return $model;
    }

    /**
     * @return array{ data: PurchaseReturn[], total: int }
     */
    public function search(SearchPurchaseReturnCommand $command): array
    {
        $query = PurchaseReturn::query()
            ->with(['supplier', 'purchaseInvoice', 'warehouse'])
            ->when($command->companyId, fn ($q) => $q->where('company_id', $command->companyId));

        $query = $this->apply($query, $command->filters);

        $total = $query->count();

        $data = $query->orderByDesc('return_date')
            ->orderByDesc('code')
            ->limit($command->limit)
            ->offset($command->offset)
            ->get();

        return ['data' => $data->all(), 'total' => $total];
    }

    /**
     * @return array<int, PurchaseReturnLine>
     */
    public function activeLines(PurchaseReturn $model): array
    {
        return PurchaseReturnLine::query()
            ->with(['item', 'purchaseInvoiceLine'])
            ->where('purchase_return_id', $model->id)
            ->where('status', 'active')
            ->orderBy('line_number')
            ->get()
            ->all();
    }

    /**
     * Cantidad ya devuelta de cada línea de factura.
     *
     * Suma solo las líneas activas de devoluciones que siguen vivas: una
     * devolución anulada devuelve su cupo, y la que se está guardando no
     * compite consigo misma.
     *
     * @param  array<int, string>  $invoiceLineIds
     * @return array<string, float>
     */
    public function returnedQuantities(array $invoiceLineIds, ?string $exceptReturnId = null): array
    {
        if ($invoiceLineIds === []) {
            return [];
        }

        return PurchaseReturnLine::query()
            ->whereIn('purchase_invoice_line_id', $invoiceLineIds)
            ->where('app_purchase_return_lines.status', 'active')
            ->when($exceptReturnId, fn ($q) => $q->where('purchase_return_id', '!=', $exceptReturnId))
            ->whereHas('purchaseReturn', fn ($q) => $q->where('status', '!=', 'cancelled'))
            ->groupBy('purchase_invoice_line_id')
            ->selectRaw('purchase_invoice_line_id, SUM(quantity) as returned')
            ->pluck('returned', 'purchase_invoice_line_id')
            ->map(fn ($returned): float => (float) $returned)
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function detailRelations(): array
    {
        return [
            'supplier',
            'purchaseInvoice',
            'warehouse',
            'creditNote',
            'lines.item',
            'lines.measurementUnit',
            'lines.warehouse',
        ];
    }

    /**
     * Alinea `app_purchase_return_lines` con lo enviado desde la pantalla.
     *
     * Las filas existentes se reconocen por su `id` y conservan su
     * `line_number`; las nuevas toman el siguiente número libre. Las que dejan
     * de venir no se borran, se desactivan (política de no borrado), y por eso
     * los números no se recalculan: el par `(devolución, line_number)` es único
     * y una fila inactiva sigue ocupando el suyo.
     *
     * @param  array<int, PurchaseReturnLineData>  $lines
     */
    private function syncLines(PurchaseReturn $return, array $lines): void
    {
        $existing = PurchaseReturnLine::query()
            ->where('purchase_return_id', $return->id)
            ->get()
            ->keyBy('id');

        $factors = $this->conversionFactors($return->company_id, $lines);
        $nextNumber = (int) $existing->max('line_number');
        $keep = [];

        foreach ($lines as $line) {
            $current = $line->id !== null ? $existing->get($line->id) : null;

            $factor = $factors[$line->itemId.'|'.$line->measurementUnitId] ?? 1.0;

            $attributes = [
                'company_id' => $return->company_id,
                'item_id' => $line->itemId,
                'measurement_unit_id' => $line->measurementUnitId,
                'warehouse_id' => $line->warehouseId,
                'purchase_invoice_line_id' => $line->purchaseInvoiceLineId,
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
                'reason' => $line->reason,
                'status' => $line->status,
                'notes' => $line->notes,
            ];

            if ($current !== null) {
                $current->update($attributes);
                $keep[] = $current->id;

                continue;
            }

            $keep[] = PurchaseReturnLine::create([
                ...$attributes,
                'purchase_return_id' => $return->id,
                'line_number' => ++$nextNumber,
            ])->id;
        }

        PurchaseReturnLine::query()
            ->where('purchase_return_id', $return->id)
            ->when($keep !== [], fn ($q) => $q->whereNotIn('id', $keep))
            ->update(['status' => 'inactive']);
    }

    /**
     * Factor de conversión a la unidad base por par artículo/unidad.
     *
     * Se resuelve a través del repositorio de artículos: el módulo de
     * devoluciones nunca consulta las tablas del módulo de inventario
     * directamente. Un par sin unidad registrada cae en 1, que es lo que valida
     * el Request.
     *
     * @param  array<int, PurchaseReturnLineData>  $lines
     * @return array<string, float>
     */
    private function conversionFactors(?string $companyId, array $lines): array
    {
        $factors = [];

        foreach (array_unique(array_map(fn (PurchaseReturnLineData $line): string => $line->itemId, $lines)) as $itemId) {
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
     * La devolución no lleva descuento global ni gastos: lo que se devuelve
     * vale lo que suman sus líneas, al precio de la compra original.
     *
     * @param  array<int, PurchaseReturnLineData>  $lines
     * @return array<string, float>
     */
    private function totals(array $lines): array
    {
        $active = array_filter($lines, fn (PurchaseReturnLineData $line): bool => $line->status === 'active');

        $subtotal = round(array_sum(array_map(fn (PurchaseReturnLineData $line): float => $line->subtotal, $active)), 2);
        $tax = round(array_sum(array_map(fn (PurchaseReturnLineData $line): float => $line->taxAmount, $active)), 2);

        return [
            'subtotal' => $subtotal,
            'tax_amount' => $tax,
            'total' => round($subtotal + $tax, 2),
        ];
    }

    /**
     * Generate the next sequential per-company code (DVC000001, DVC000002, …).
     */
    private function generateNextCode(string $companyId): string
    {
        $last = PurchaseReturn::query()
            ->where('company_id', $companyId)
            ->where('code', 'like', PurchaseReturn::CODE_PREFIX.'%')
            ->lockForUpdate()
            ->orderByDesc('code')
            ->value('code');

        $next = $last !== null
            ? ((int) substr($last, strlen(PurchaseReturn::CODE_PREFIX))) + 1
            : 1;

        return PurchaseReturn::CODE_PREFIX.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
