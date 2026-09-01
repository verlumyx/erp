<?php

declare(strict_types=1);

namespace App\Modules\SalesReturn\Repositories;

use App\Modules\ExchangeRate\Commands\DocumentRatesData;
use App\Modules\Item\Models\Item;
use App\Modules\Item\Models\ItemUnit;
use App\Modules\Item\Repositories\Contracts\ItemRepositoryInterface;
use App\Modules\SalesReturn\Commands\CreateSalesReturnCommand;
use App\Modules\SalesReturn\Commands\SalesReturnLineData;
use App\Modules\SalesReturn\Commands\SearchSalesReturnCommand;
use App\Modules\SalesReturn\Commands\UpdateSalesReturnCommand;
use App\Modules\SalesReturn\Commands\UpdateStatusSalesReturnCommand;
use App\Modules\SalesReturn\Commands\WriteSalesReturnCreditNoteCommand;
use App\Modules\SalesReturn\Models\SalesReturn;
use App\Modules\SalesReturn\Models\SalesReturnLine;
use App\Modules\SalesReturn\Repositories\Contracts\SalesReturnRepositoryInterface;
use Illuminate\Support\Facades\DB;

class SalesReturnRepository extends SalesReturnFilters implements SalesReturnRepositoryInterface
{
    public function __construct(
        private readonly ItemRepositoryInterface $items,
    ) {}

    /**
     * @param  array<int, float>  $unitCosts
     */
    public function create(CreateSalesReturnCommand $command, DocumentRatesData $rates, array $unitCosts): void
    {
        DB::transaction(function () use ($command, $rates, $unitCosts): void {
            $return = SalesReturn::create([
                'id' => $command->id,
                'company_id' => $command->companyId,
                'code' => $this->generateNextCode($command->companyId),
                'client_id' => $command->clientId,
                'sales_invoice_id' => $command->salesInvoiceId,
                'dispatch_id' => $command->dispatchId,
                'warehouse_id' => $command->warehouseId,
                'return_date' => $command->returnDate,
                'reason' => $command->reason,
                'reason_detail' => $command->reasonDetail,
                'condition' => $command->condition,
                ...$rates->toAttributes(),
                ...$this->totals($command->lines),
                'received_by' => $command->receivedBy,
                'notes' => $command->notes,
                'status' => 'draft',
                'created_by' => $command->createdBy,
            ]);

            $this->syncLines($return, $command->lines, $unitCosts);
        });
    }

    public function findById(string $id, ?string $companyId = null): ?SalesReturn
    {
        return SalesReturn::query()
            ->with($this->detailRelations())
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->find($id);
    }

    public function findOrFail(string $id, ?string $companyId = null): SalesReturn
    {
        return SalesReturn::query()
            ->with($this->detailRelations())
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->findOrFail($id);
    }

    /**
     * @param  array<int, float>  $unitCosts
     */
    public function update(
        SalesReturn $model,
        UpdateSalesReturnCommand $command,
        DocumentRatesData $rates,
        array $unitCosts,
    ): void {
        DB::transaction(function () use ($model, $command, $rates, $unitCosts): void {
            /** La nota de crédito generada y la marca de anulación no se editan aquí. */
            $model->update([
                'client_id' => $command->clientId,
                'sales_invoice_id' => $command->salesInvoiceId,
                'dispatch_id' => $command->dispatchId,
                'warehouse_id' => $command->warehouseId,
                'return_date' => $command->returnDate,
                'reason' => $command->reason,
                'reason_detail' => $command->reasonDetail,
                'condition' => $command->condition,
                ...$rates->toAttributes(),
                ...$this->totals($command->lines),
                'received_by' => $command->receivedBy,
                'notes' => $command->notes,
            ]);

            $this->syncLines($model, $command->lines, $unitCosts);
        });
    }

    public function updateStatus(SalesReturn $model, UpdateStatusSalesReturnCommand $command): void
    {
        $attributes = ['status' => $command->status];

        if ($command->status === 'cancelled') {
            $attributes['cancelled_at'] = now();
        }

        $model->update($attributes);
    }

    public function lockById(string $id, ?string $companyId = null): ?SalesReturn
    {
        return SalesReturn::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->lockForUpdate()
            ->find($id);
    }

    public function writeCreditNote(
        SalesReturn $model,
        WriteSalesReturnCreditNoteCommand $command,
    ): SalesReturn {
        $model->update([
            'credit_note_id' => $command->creditNoteId,
            'status' => $command->status,
        ]);

        return $model;
    }

    /**
     * @return array{ data: SalesReturn[], total: int }
     */
    public function search(SearchSalesReturnCommand $command): array
    {
        $query = SalesReturn::query()
            ->with(['client', 'salesInvoice', 'warehouse'])
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
     * @return array<int, SalesReturnLine>
     */
    public function activeLines(SalesReturn $model): array
    {
        return SalesReturnLine::query()
            ->with(['item', 'salesInvoiceLine'])
            ->where('sales_return_id', $model->id)
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

        return SalesReturnLine::query()
            ->whereIn('sales_invoice_line_id', $invoiceLineIds)
            ->where('app_sales_return_lines.status', 'active')
            ->when($exceptReturnId, fn ($q) => $q->where('sales_return_id', '!=', $exceptReturnId))
            ->whereHas('salesReturn', fn ($q) => $q->where('status', '!=', 'cancelled'))
            ->groupBy('sales_invoice_line_id')
            ->selectRaw('sales_invoice_line_id, SUM(quantity) as returned')
            ->pluck('returned', 'sales_invoice_line_id')
            ->map(fn ($returned): float => (float) $returned)
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function detailRelations(): array
    {
        return [
            'client',
            'salesInvoice',
            'warehouse',
            'creditNote',
            'receiver',
            'lines.item',
            'lines.measurementUnit',
            'lines.lot',
            'lines.serial',
            'lines.location',
        ];
    }

    /**
     * Alinea `app_sales_return_lines` con lo enviado desde la pantalla.
     *
     * Las filas existentes se reconocen por su `id` y conservan su
     * `line_number`; las nuevas toman el siguiente número libre. Las que dejan
     * de venir no se borran, se desactivan (política de no borrado), y por eso
     * los números no se recalculan: el par `(devolución, line_number)` es único
     * y una fila inactiva sigue ocupando el suyo.
     *
     * @param  array<int, SalesReturnLineData>  $lines
     * @param  array<int, float>  $unitCosts  Costo de reingreso ya resuelto,
     *                                        con la misma clave que la línea.
     */
    private function syncLines(SalesReturn $return, array $lines, array $unitCosts): void
    {
        $existing = SalesReturnLine::query()
            ->where('sales_return_id', $return->id)
            ->get()
            ->keyBy('id');

        $factors = $this->conversionFactors($return->company_id, $lines);
        $nextNumber = (int) $existing->max('line_number');
        $keep = [];

        foreach ($lines as $index => $line) {
            $current = $line->id !== null ? $existing->get($line->id) : null;

            $factor = $factors[$line->itemId.'|'.$line->measurementUnitId] ?? 1.0;

            $attributes = [
                'company_id' => $return->company_id,
                'item_id' => $line->itemId,
                'measurement_unit_id' => $line->measurementUnitId,
                'sales_invoice_line_id' => $line->salesInvoiceLineId,
                'lot_id' => $line->lotId,
                'serial_id' => $line->serialId,
                'location_id' => $line->locationId,
                'quantity' => $line->quantity,
                'base_quantity' => round($line->quantity * $factor, 4),
                'unit_price' => $line->unitPrice,
                'unit_cost' => round($unitCosts[$index] ?? 0.0, 6),
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
                'condition' => $line->condition,
                'status' => $line->status,
                'notes' => $line->notes,
            ];

            if ($current !== null) {
                $current->update($attributes);
                $keep[] = $current->id;

                continue;
            }

            $keep[] = SalesReturnLine::create([
                ...$attributes,
                'sales_return_id' => $return->id,
                'line_number' => ++$nextNumber,
            ])->id;
        }

        SalesReturnLine::query()
            ->where('sales_return_id', $return->id)
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
     * @param  array<int, SalesReturnLineData>  $lines
     * @return array<string, float>
     */
    private function conversionFactors(?string $companyId, array $lines): array
    {
        $factors = [];

        foreach (array_unique(array_map(fn (SalesReturnLineData $line): string => $line->itemId, $lines)) as $itemId) {
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
     * vale lo que suman sus líneas, al precio de la venta original.
     *
     * @param  array<int, SalesReturnLineData>  $lines
     * @return array<string, float>
     */
    private function totals(array $lines): array
    {
        $active = array_filter($lines, fn (SalesReturnLineData $line): bool => $line->status === 'active');

        $subtotal = round(array_sum(array_map(fn (SalesReturnLineData $line): float => $line->subtotal, $active)), 2);
        $tax = round(array_sum(array_map(fn (SalesReturnLineData $line): float => $line->taxAmount, $active)), 2);

        return [
            'subtotal' => $subtotal,
            'tax_amount' => $tax,
            'total' => round($subtotal + $tax, 2),
        ];
    }

    /**
     * Generate the next sequential per-company code (DVV000001, DVV000002, …).
     */
    private function generateNextCode(string $companyId): string
    {
        $last = SalesReturn::query()
            ->where('company_id', $companyId)
            ->where('code', 'like', SalesReturn::CODE_PREFIX.'%')
            ->lockForUpdate()
            ->orderByDesc('code')
            ->value('code');

        $next = $last !== null
            ? ((int) substr($last, strlen(SalesReturn::CODE_PREFIX))) + 1
            : 1;

        return SalesReturn::CODE_PREFIX.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
