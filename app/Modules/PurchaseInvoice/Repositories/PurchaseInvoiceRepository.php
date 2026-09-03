<?php

declare(strict_types=1);

namespace App\Modules\PurchaseInvoice\Repositories;

use App\Modules\ExchangeRate\Commands\DocumentRatesData;
use App\Modules\Item\Models\Item;
use App\Modules\Item\Models\ItemUnit;
use App\Modules\Item\Repositories\Contracts\ItemRepositoryInterface;
use App\Modules\PurchaseInvoice\Commands\CreatePurchaseInvoiceCommand;
use App\Modules\PurchaseInvoice\Commands\PurchaseInvoiceLineData;
use App\Modules\PurchaseInvoice\Commands\SearchPurchaseInvoiceCommand;
use App\Modules\PurchaseInvoice\Commands\UpdatePurchaseInvoiceCommand;
use App\Modules\PurchaseInvoice\Commands\UpdateStatusPurchaseInvoiceCommand;
use App\Modules\PurchaseInvoice\Commands\WritePurchaseInvoiceLineReturnCommand;
use App\Modules\PurchaseInvoice\Commands\WritePurchaseInvoicePaymentCommand;
use App\Modules\PurchaseInvoice\Models\PurchaseInvoice;
use App\Modules\PurchaseInvoice\Models\PurchaseInvoiceLine;
use App\Modules\PurchaseInvoice\Repositories\Contracts\PurchaseInvoiceRepositoryInterface;
use Illuminate\Support\Facades\DB;

class PurchaseInvoiceRepository extends PurchaseInvoiceFilters implements PurchaseInvoiceRepositoryInterface
{
    public function __construct(
        private readonly ItemRepositoryInterface $items,
    ) {}

    public function create(CreatePurchaseInvoiceCommand $command, DocumentRatesData $rates, string $dueDate): void
    {
        DB::transaction(function () use ($command, $rates, $dueDate): void {
            $invoice = PurchaseInvoice::create([
                'id' => $command->id,
                'company_id' => $command->companyId,
                'code' => $this->generateNextCode($command->companyId),
                'supplier_id' => $command->supplierId,
                'sourceable_type' => $command->sourceableType,
                'sourceable_id' => $command->sourceableId,
                'entry_id' => $command->entryId,
                'warehouse_id' => $command->warehouseId,
                'supplier_invoice_number' => $command->supplierInvoiceNumber,
                'supplier_invoice_series' => $command->supplierInvoiceSeries,
                'invoice_date' => $command->invoiceDate,
                'received_date' => $command->receivedDate,
                'due_date' => $dueDate,
                ...$rates->toAttributes(),
                ...$this->totals($command->lines, $command->discountAmount, $rates),
                /** La deuda nace entera: la mueven Pagos, Anticipos y Notas de crédito. */
                'paid_amount' => 0,
                'payment_status' => 'pending',
                'notes' => $command->notes,
                'status' => 'draft',
                'created_by' => $command->createdBy,
            ]);

            $this->syncLines($invoice, $command->lines);
        });
    }

    public function findById(string $id, ?string $companyId = null): ?PurchaseInvoice
    {
        return PurchaseInvoice::query()
            ->with($this->detailRelations())
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->find($id);
    }

    public function findOrFail(string $id, ?string $companyId = null): PurchaseInvoice
    {
        return PurchaseInvoice::query()
            ->with($this->detailRelations())
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->findOrFail($id);
    }

    public function update(
        PurchaseInvoice $model,
        UpdatePurchaseInvoiceCommand $command,
        DocumentRatesData $rates,
        string $dueDate,
    ): void {
        DB::transaction(function () use ($model, $command, $rates, $dueDate): void {
            /** Lo aplicado por pagos y las marcas de anulación no se editan aquí. */
            $model->update([
                'supplier_id' => $command->supplierId,
                'sourceable_type' => $command->sourceableType,
                'sourceable_id' => $command->sourceableId,
                'entry_id' => $command->entryId,
                'warehouse_id' => $command->warehouseId,
                'supplier_invoice_number' => $command->supplierInvoiceNumber,
                'supplier_invoice_series' => $command->supplierInvoiceSeries,
                'invoice_date' => $command->invoiceDate,
                'received_date' => $command->receivedDate,
                'due_date' => $dueDate,
                ...$rates->toAttributes(),
                ...$this->totals(
                    $command->lines,
                    $command->discountAmount,
                    $rates,
                    (float) $model->paid_amount,
                ),
                'notes' => $command->notes,
            ]);

            $this->syncLines($model, $command->lines);
        });
    }

    public function updateStatus(PurchaseInvoice $model, UpdateStatusPurchaseInvoiceCommand $command): void
    {
        $attributes = ['status' => $command->status];

        if ($command->status === 'cancelled') {
            $attributes['cancelled_at'] = now();
            $attributes['cancellation_reason'] = $command->cancellationReason;
        }

        $model->update($attributes);
    }

    /**
     * @return array<int, PurchaseInvoiceLine>
     */
    public function activeLines(PurchaseInvoice $model): array
    {
        return PurchaseInvoiceLine::query()
            ->with(['item'])
            ->where('purchase_invoice_id', $model->id)
            ->where('status', 'active')
            ->orderBy('line_number')
            ->get()
            ->all();
    }

    public function markOverdue(string $onDate, ?string $companyId = null): int
    {
        return PurchaseInvoice::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereIn('status', PurchaseInvoice::PAYABLE_STATUSES)
            ->whereIn('payment_status', ['pending', 'partial'])
            ->where('balance', '>', 0)
            ->whereDate('due_date', '<', $onDate)
            ->update(['payment_status' => 'overdue']);
    }

    public function lockById(string $id, ?string $companyId = null): ?PurchaseInvoice
    {
        return PurchaseInvoice::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->lockForUpdate()
            ->find($id);
    }

    public function writePayment(PurchaseInvoice $model, WritePurchaseInvoicePaymentCommand $command): PurchaseInvoice
    {
        $model->update([
            'paid_amount' => $command->paidAmount,
            'balance' => $command->balance,
            'payment_status' => $command->paymentStatus,
        ]);

        return $model;
    }

    public function lockLineById(string $id, ?string $companyId = null): ?PurchaseInvoiceLine
    {
        return PurchaseInvoiceLine::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->lockForUpdate()
            ->find($id);
    }

    public function writeLineReturn(
        PurchaseInvoiceLine $line,
        WritePurchaseInvoiceLineReturnCommand $command,
    ): PurchaseInvoiceLine {
        $line->update(['returned_quantity' => $command->returnedQuantity]);

        return $line;
    }

    /**
     * @return array{ data: PurchaseInvoice[], total: int }
     */
    public function search(SearchPurchaseInvoiceCommand $command): array
    {
        $query = PurchaseInvoice::query()
            ->with(['supplier', 'warehouse'])
            ->when($command->companyId, fn ($q) => $q->where('company_id', $command->companyId));

        $query = $this->apply($query, $command->filters);

        $total = $query->count();

        $data = $query->orderByDesc('invoice_date')
            ->orderByDesc('code')
            ->limit($command->limit)
            ->offset($command->offset)
            ->get();

        return ['data' => $data->all(), 'total' => $total];
    }

    /**
     * @return array<int, string>
     */
    private function detailRelations(): array
    {
        return ['supplier', 'warehouse', 'sourceable', 'lines.item', 'lines.measurementUnit'];
    }

    /**
     * Alinea `app_purchase_invoice_lines` con lo enviado desde la pantalla.
     *
     * Las filas existentes se reconocen por su `id` y conservan su
     * `line_number`; las nuevas toman el siguiente número libre. Las que dejan
     * de venir no se borran, se desactivan (política de no borrado), y por eso
     * los números no se recalculan: el par `(factura, line_number)` es único y
     * una fila inactiva sigue ocupando el suyo.
     *
     * @param  array<int, PurchaseInvoiceLineData>  $lines
     */
    private function syncLines(PurchaseInvoice $invoice, array $lines): void
    {
        $existing = PurchaseInvoiceLine::query()
            ->where('purchase_invoice_id', $invoice->id)
            ->get()
            ->keyBy('id');

        $factors = $this->conversionFactors($invoice->company_id, $lines);
        $nextNumber = (int) $existing->max('line_number');
        $keep = [];

        foreach ($lines as $index => $line) {
            $current = $line->id !== null ? $existing->get($line->id) : null;

            /** Lo ya devuelto pertenece al histórico de la línea: nunca se pisa. */
            $returned = (float) ($current->returned_quantity ?? 0);
            $factor = $factors[$line->itemId.'|'.$line->measurementUnitId] ?? 1.0;
            $baseQuantity = round($line->quantity * $factor, 4);

            $attributes = [
                'company_id' => $invoice->company_id,
                'item_id' => $line->itemId,
                'measurement_unit_id' => $line->measurementUnitId,
                'sourceable_type' => $line->sourceableType,
                'sourceable_id' => $line->sourceableId,
                'warehouse_id' => $line->warehouseId,
                'lot_id' => $line->lotId,
                'quantity' => $line->quantity,
                'base_quantity' => $baseQuantity,
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
                'landed_cost' => $this->landedCost($line, $baseQuantity),
                'returned_quantity' => $returned,
                'status' => $line->status,
                'notes' => $line->notes,
            ];

            if ($current !== null) {
                $current->update($attributes);
                $keep[] = $current->id;

                continue;
            }

            $keep[] = PurchaseInvoiceLine::create([
                ...$attributes,
                'purchase_invoice_id' => $invoice->id,
                'line_number' => ++$nextNumber,
            ])->id;
        }

        PurchaseInvoiceLine::query()
            ->where('purchase_invoice_id', $invoice->id)
            ->when($keep !== [], fn ($q) => $q->whereNotIn('id', $keep))
            ->update(['status' => 'inactive']);
    }

    /**
     * Costo unitario de la línea: su importe repartido entre la cantidad en
     * unidad base.
     *
     * Lo que costó **traer** la mercancía ya no se prorratea aquí. El flete y
     * los demás cargos que el proveedor cobre en el mismo papel van como una
     * línea más de la factura, y lo que cuesta ponerla en la bodega lo reparte
     * el expediente de Importaciones sobre la entrada que la recibió.
     */
    private function landedCost(PurchaseInvoiceLineData $line, float $baseQuantity): float
    {
        if ($baseQuantity <= 0) {
            return 0.0;
        }

        return round($line->subtotal / $baseQuantity, 6);
    }

    /**
     * Factor de conversión a la unidad base por par artículo/unidad.
     *
     * Se resuelve a través del repositorio de artículos: el módulo de facturas
     * nunca consulta las tablas del módulo de inventario directamente. Un par
     * sin unidad registrada cae en 1, que es lo que valida el Request.
     *
     * @param  array<int, PurchaseInvoiceLineData>  $lines
     * @return array<string, float>
     */
    private function conversionFactors(?string $companyId, array $lines): array
    {
        $factors = [];

        foreach (array_unique(array_map(fn (PurchaseInvoiceLineData $line): string => $line->itemId, $lines)) as $itemId) {
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
     * Totales de la cabecera. Suman **solo** las líneas activas, y el descuento
     * global se resta después del subtotal. Un flete cobrado en el mismo papel
     * es una línea más, así que ya viene dentro del subtotal.
     *
     * La retención no se resta del total: es una parte del impuesto que se
     * entera al fisco en vez de pagarse al proveedor, así que baja lo que se le
     * paga (`total - withholding_amount`), no lo que la factura vale.
     *
     * @param  array<int, PurchaseInvoiceLineData>  $lines
     * @return array<string, float>
     */
    private function totals(
        array $lines,
        float $discountAmount,
        DocumentRatesData $rates,
        float $paidAmount = 0,
    ): array {
        $active = array_filter($lines, fn (PurchaseInvoiceLineData $line): bool => $line->status === 'active');

        $subtotal = round(array_sum(array_map(fn (PurchaseInvoiceLineData $line): float => $line->subtotal, $active)), 2);
        $tax = round(array_sum(array_map(fn (PurchaseInvoiceLineData $line): float => $line->taxAmount, $active)), 2);
        $withholding = round(array_sum(array_map(fn (PurchaseInvoiceLineData $line): float => $line->withholdingAmount, $active)), 2);

        $discount = round($discountAmount, 2);

        $total = round($subtotal - $discount + $tax, 2);

        return [
            'subtotal' => $subtotal,
            'discount_amount' => $discount,
            'tax_amount' => $tax,
            'withholding_amount' => $withholding,
            'total' => $total,
            /** La factura tiene valor legal: su deuda en bolívares queda escrita. */
            'subtotal_ves' => $this->inLocalCurrency($subtotal, $rates),
            'tax_amount_ves' => $this->inLocalCurrency($tax, $rates),
            'total_ves' => $this->inLocalCurrency($total, $rates),
            'balance' => round($total - $paidAmount, 2),
        ];
    }

    /** Importe en bolívares con los decimales que la empresa usa para importes. */
    private function inLocalCurrency(float $amount, DocumentRatesData $rates): float
    {
        return round($amount * $rates->exchangeRate, $rates->amountDecimals);
    }

    /**
     * Generate the next sequential per-company code (FCO000001, FCO000002, …).
     */
    private function generateNextCode(string $companyId): string
    {
        $last = PurchaseInvoice::query()
            ->where('company_id', $companyId)
            ->where('code', 'like', PurchaseInvoice::CODE_PREFIX.'%')
            ->lockForUpdate()
            ->orderByDesc('code')
            ->value('code');

        $next = $last !== null
            ? ((int) substr($last, strlen(PurchaseInvoice::CODE_PREFIX))) + 1
            : 1;

        return PurchaseInvoice::CODE_PREFIX.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
