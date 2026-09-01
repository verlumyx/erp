<?php

declare(strict_types=1);

namespace App\Modules\SalesCreditNote\Repositories;

use App\Modules\Client\Models\Client;
use App\Modules\ExchangeRate\Commands\DocumentRatesData;
use App\Modules\Item\Models\Item;
use App\Modules\Item\Models\ItemUnit;
use App\Modules\Item\Repositories\Contracts\ItemRepositoryInterface;
use App\Modules\SalesCreditNote\Commands\CreateSalesCreditNoteCommand;
use App\Modules\SalesCreditNote\Commands\SalesCreditNoteLineData;
use App\Modules\SalesCreditNote\Commands\SearchSalesCreditNoteCommand;
use App\Modules\SalesCreditNote\Commands\UpdateSalesCreditNoteCommand;
use App\Modules\SalesCreditNote\Commands\UpdateStatusSalesCreditNoteCommand;
use App\Modules\SalesCreditNote\Models\SalesCreditNote;
use App\Modules\SalesCreditNote\Models\SalesCreditNoteLine;
use App\Modules\SalesCreditNote\Repositories\Contracts\SalesCreditNoteRepositoryInterface;
use App\Modules\SalesInvoice\Models\SalesInvoiceLine;
use Illuminate\Support\Facades\DB;

class SalesCreditNoteRepository extends SalesCreditNoteFilters implements SalesCreditNoteRepositoryInterface
{
    /** Longitud del correlativo fiscal dentro de su serie. */
    private const NOTE_NUMBER_LENGTH = 8;

    public function __construct(
        private readonly ItemRepositoryInterface $items,
    ) {}

    public function create(CreateSalesCreditNoteCommand $command, DocumentRatesData $rates): void
    {
        DB::transaction(function () use ($command, $rates): void {
            $note = SalesCreditNote::create([
                'id' => $command->id,
                'company_id' => $command->companyId,
                'code' => $this->generateNextCode($command->companyId),
                'client_id' => $command->clientId,
                'sales_invoice_id' => $command->salesInvoiceId,
                'sales_return_id' => $command->salesReturnId,
                'note_series' => $command->noteSeries,
                /** El correlativo fiscal se quema al confirmar, no antes. */
                'note_number' => null,
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

    public function findById(string $id, ?string $companyId = null): ?SalesCreditNote
    {
        return SalesCreditNote::query()
            ->with($this->detailRelations())
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->find($id);
    }

    public function findOrFail(string $id, ?string $companyId = null): SalesCreditNote
    {
        return SalesCreditNote::query()
            ->with($this->detailRelations())
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->findOrFail($id);
    }

    public function update(
        SalesCreditNote $model,
        UpdateSalesCreditNoteCommand $command,
        DocumentRatesData $rates,
    ): void {
        DB::transaction(function () use ($model, $command, $rates): void {
            /** Lo ya aplicado a facturas y la marca de anulación no se editan aquí. */
            $model->update([
                'client_id' => $command->clientId,
                'sales_invoice_id' => $command->salesInvoiceId,
                'sales_return_id' => $command->salesReturnId,
                'note_series' => $command->noteSeries,
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
     * Confirmarla quema el correlativo fiscal y baja la cuenta por cobrar del
     * cliente; anularla ya confirmada la devuelve. Un borrador anulado no
     * revierte nada: nunca llegó a bajar ningún saldo.
     */
    public function updateStatus(SalesCreditNote $model, UpdateStatusSalesCreditNoteCommand $command): void
    {
        DB::transaction(function () use ($model, $command): void {
            if ($command->status === 'confirmed') {
                $model->update([
                    'status' => 'confirmed',
                    'note_number' => $model->note_number ?? $this->generateNextNoteNumber($model),
                ]);

                $this->moveClientBalance($model, -(float) $model->total);

                return;
            }

            if ($command->status === 'cancelled') {
                if ($model->status !== 'draft') {
                    $this->moveClientBalance($model, (float) $model->total);
                }

                $model->update(['status' => 'cancelled', 'cancelled_at' => now()]);

                return;
            }

            $model->update(['status' => $command->status]);
        });
    }

    /**
     * @return array{ data: SalesCreditNote[], total: int }
     */
    public function search(SearchSalesCreditNoteCommand $command): array
    {
        $query = SalesCreditNote::query()
            ->with(['client', 'salesInvoice'])
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
     * @return array<int, SalesCreditNoteLine>
     */
    public function activeLines(SalesCreditNote $note): array
    {
        return SalesCreditNoteLine::query()
            ->with(['item', 'salesInvoiceLine'])
            ->where('sales_credit_note_id', $note->id)
            ->where('status', 'active')
            ->orderBy('line_number')
            ->get()
            ->all();
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

        return SalesCreditNoteLine::query()
            ->whereIn('sales_invoice_line_id', $invoiceLineIds)
            ->where('app_sales_credit_note_lines.status', 'active')
            ->when($exceptNoteId, fn ($q) => $q->where('sales_credit_note_id', '!=', $exceptNoteId))
            ->whereHas('salesCreditNote', fn ($q) => $q->where('status', '!=', 'cancelled'))
            ->groupBy('sales_invoice_line_id')
            ->selectRaw('sales_invoice_line_id, SUM(quantity) as credited')
            ->pluck('credited', 'sales_invoice_line_id')
            ->map(fn ($credited): float => (float) $credited)
            ->all();
    }

    public function creditedAmount(string $invoiceId, ?string $exceptNoteId = null): float
    {
        return round((float) SalesCreditNote::query()
            ->where('sales_invoice_id', $invoiceId)
            ->where('status', '!=', 'cancelled')
            ->when($exceptNoteId, fn ($q) => $q->where('id', '!=', $exceptNoteId))
            ->sum('total'), 2);
    }

    /**
     * @return array<int, string>
     */
    private function detailRelations(): array
    {
        return ['client', 'salesInvoice', 'lines.item', 'lines.measurementUnit'];
    }

    /**
     * Alinea `app_sales_credit_note_lines` con lo enviado desde la pantalla.
     *
     * Las filas existentes se reconocen por su `id` y conservan su
     * `line_number`; las nuevas toman el siguiente número libre. Las que dejan
     * de venir no se borran, se desactivan (política de no borrado), y por eso
     * los números no se recalculan: el par `(nota, line_number)` es único y una
     * fila inactiva sigue ocupando el suyo.
     *
     * @param  array<int, SalesCreditNoteLineData>  $lines
     */
    private function syncLines(SalesCreditNote $note, array $lines): void
    {
        $existing = SalesCreditNoteLine::query()
            ->where('sales_credit_note_id', $note->id)
            ->get()
            ->keyBy('id');

        $factors = $this->conversionFactors($note->company_id, $lines);
        $costs = $this->unitCosts($note->company_id, $lines);
        $nextNumber = (int) $existing->max('line_number');
        $keep = [];

        foreach ($lines as $line) {
            $current = $line->id !== null ? $existing->get($line->id) : null;

            $factor = $factors[$line->itemId.'|'.$line->measurementUnitId] ?? 1.0;

            $attributes = [
                'company_id' => $note->company_id,
                'item_id' => $line->itemId,
                'measurement_unit_id' => $line->measurementUnitId,
                'sales_invoice_line_id' => $line->salesInvoiceLineId,
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
                /** La mercancía reingresa al costo con el que salió en la venta. */
                'unit_cost' => $costs[$this->costKey($line)] ?? 0.0,
                'status' => $line->status,
                'notes' => $line->notes,
            ];

            if ($current !== null) {
                $current->update($attributes);
                $keep[] = $current->id;

                continue;
            }

            $keep[] = SalesCreditNoteLine::create([
                ...$attributes,
                'sales_credit_note_id' => $note->id,
                'line_number' => ++$nextNumber,
            ])->id;
        }

        SalesCreditNoteLine::query()
            ->where('sales_credit_note_id', $note->id)
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
     * @param  array<int, SalesCreditNoteLineData>  $lines
     * @return array<string, float>
     */
    private function conversionFactors(?string $companyId, array $lines): array
    {
        $factors = [];

        foreach ($this->itemIdsOf($lines) as $itemId) {
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
     * Costo unitario con el que cada línea reingresa la mercancía.
     *
     * Manda el costo congelado en la línea de la factura: es el que salió con
     * la venta, y devolverla no puede inventar ni destruir margen. Una línea
     * suelta —sin factura detrás— se valora al costo vigente del artículo.
     *
     * @param  array<int, SalesCreditNoteLineData>  $lines
     * @return array<string, float>
     */
    private function unitCosts(?string $companyId, array $lines): array
    {
        $invoiceCosts = SalesInvoiceLine::query()
            ->whereIn('id', array_values(array_filter(array_map(
                static fn (SalesCreditNoteLineData $line): ?string => $line->salesInvoiceLineId,
                $lines,
            ))))
            ->pluck('unit_cost', 'id')
            ->map(fn ($cost): float => round((float) $cost, 6))
            ->all();

        $costs = [];

        foreach ($this->itemIdsOf($lines) as $itemId) {
            $item = $this->items->findById($itemId, $companyId);

            $costs[$itemId] = $item instanceof Item
                ? round((float) ($item->cost_method === 'standard' ? $item->standard_cost : $item->average_cost), 6)
                : 0.0;
        }

        $resolved = [];

        foreach ($lines as $line) {
            $fromInvoice = $line->salesInvoiceLineId !== null
                ? ($invoiceCosts[$line->salesInvoiceLineId] ?? 0.0)
                : 0.0;

            $resolved[$this->costKey($line)] = $fromInvoice > 0
                ? $fromInvoice
                : ($costs[$line->itemId] ?? 0.0);
        }

        return $resolved;
    }

    /** Identifica a la línea dentro del envío: dos filas pueden repetir artículo. */
    private function costKey(SalesCreditNoteLineData $line): string
    {
        return $line->itemId.'|'.($line->salesInvoiceLineId ?? '');
    }

    /**
     * @param  array<int, SalesCreditNoteLineData>  $lines
     * @return array<int, string>
     */
    private function itemIdsOf(array $lines): array
    {
        return array_values(array_unique(array_map(
            static fn (SalesCreditNoteLineData $line): string => $line->itemId,
            $lines,
        )));
    }

    /**
     * Totales de la cabecera. Suman **solo** las líneas activas.
     *
     * La nota no lleva descuento global ni gastos: lo que acredita es lo que
     * suman sus líneas. `balance` es el crédito que queda disponible después de
     * lo ya aplicado a facturas.
     *
     * @param  array<int, SalesCreditNoteLineData>  $lines
     * @return array<string, float>
     */
    private function totals(array $lines, DocumentRatesData $rates, float $appliedAmount = 0): array
    {
        $active = array_filter($lines, fn (SalesCreditNoteLineData $line): bool => $line->status === 'active');

        $subtotal = round(array_sum(array_map(fn (SalesCreditNoteLineData $line): float => $line->subtotal, $active)), 2);
        $tax = round(array_sum(array_map(fn (SalesCreditNoteLineData $line): float => $line->taxAmount, $active)), 2);
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
     * Mueve la cuenta por cobrar del cliente. La nota la baja al confirmarse y
     * la devuelve al anularse: son las dos únicas veces que la toca.
     */
    private function moveClientBalance(SalesCreditNote $note, float $amount): void
    {
        $client = Client::query()->find($note->client_id);

        if ($client === null) {
            return;
        }

        $client->update([
            'current_balance' => round((float) $client->current_balance + $amount, 2),
        ]);
    }

    /**
     * Generate the next sequential per-company code (NCC000001, NCC000002, …).
     */
    private function generateNextCode(string $companyId): string
    {
        $last = SalesCreditNote::query()
            ->where('company_id', $companyId)
            ->where('code', 'like', SalesCreditNote::CODE_PREFIX.'%')
            ->lockForUpdate()
            ->orderByDesc('code')
            ->value('code');

        $next = $last !== null
            ? ((int) substr($last, strlen(SalesCreditNote::CODE_PREFIX))) + 1
            : 1;

        return SalesCreditNote::CODE_PREFIX.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Correlativo fiscal siguiente dentro de la serie. Es independiente del
     * `code`: la serie la autoriza el fisco y cada una lleva su propia cuenta.
     */
    private function generateNextNoteNumber(SalesCreditNote $note): string
    {
        $last = SalesCreditNote::query()
            ->where('company_id', $note->company_id)
            ->when(
                $note->note_series === null,
                fn ($q) => $q->whereNull('note_series'),
                fn ($q) => $q->where('note_series', $note->note_series),
            )
            ->whereNotNull('note_number')
            ->lockForUpdate()
            ->orderByDesc('note_number')
            ->value('note_number');

        $next = $last !== null ? ((int) $last) + 1 : 1;

        return str_pad((string) $next, self::NOTE_NUMBER_LENGTH, '0', STR_PAD_LEFT);
    }
}
