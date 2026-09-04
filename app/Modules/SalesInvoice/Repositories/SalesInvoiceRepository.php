<?php

declare(strict_types=1);

namespace App\Modules\SalesInvoice\Repositories;

use App\Modules\Client\Models\Client;
use App\Modules\ExchangeRate\Commands\DocumentRatesData;
use App\Modules\Item\Models\Item;
use App\Modules\Item\Models\ItemUnit;
use App\Modules\SalesInvoice\Commands\CreateSalesInvoiceCommand;
use App\Modules\SalesInvoice\Commands\SalesInvoiceLineData;
use App\Modules\SalesInvoice\Commands\SearchSalesInvoiceCommand;
use App\Modules\SalesInvoice\Commands\UpdateSalesInvoiceCommand;
use App\Modules\SalesInvoice\Commands\UpdateStatusSalesInvoiceCommand;
use App\Modules\SalesInvoice\Commands\WriteSalesInvoiceCollectionCommand;
use App\Modules\SalesInvoice\Commands\WriteSalesInvoiceLineCostCommand;
use App\Modules\SalesInvoice\Commands\WriteSalesInvoiceLineReturnCommand;
use App\Modules\SalesInvoice\Models\SalesInvoice;
use App\Modules\SalesInvoice\Models\SalesInvoiceLine;
use App\Modules\SalesInvoice\Repositories\Contracts\SalesInvoiceRepositoryInterface;
use App\Modules\SalesOrder\Models\SalesOrder;
use App\Modules\SalesOrder\Models\SalesOrderLine;
use Illuminate\Support\Facades\DB;

class SalesInvoiceRepository extends SalesInvoiceFilters implements SalesInvoiceRepositoryInterface
{
    /** Relaciones que necesita la pantalla de detalle y el formulario de edición. */
    private const DETAIL_RELATIONS = [
        'lines.item',
        'lines.measurementUnit',
        'client',
        'clientAddress',
        'warehouse',
        'salesperson',
        'sourceable',
    ];

    /** Largo del correlativo fiscal dentro de su serie. */
    private const INVOICE_NUMBER_LENGTH = 8;

    public function create(CreateSalesInvoiceCommand $command, DocumentRatesData $rates): void
    {
        DB::transaction(function () use ($command, $rates): void {
            $invoice = SalesInvoice::create([
                'id' => $command->id,
                'company_id' => $command->companyId,
                'code' => $this->generateNextCode($command->companyId),
                'client_id' => $command->clientId,
                'sourceable_type' => $command->sourceableType,
                'sourceable_id' => $command->sourceableId,
                'dispatch_id' => $command->dispatchId,
                'client_address_id' => $command->clientAddressId,
                'warehouse_id' => $command->warehouseId,
                'salesperson_id' => $command->salespersonId,
                'invoice_series' => $command->invoiceSeries,
                /** El correlativo fiscal se quema al confirmar, no antes. */
                'invoice_number' => null,
                'invoice_date' => $command->invoiceDate,
                'due_date' => $command->dueDate,
                'sale_type' => $command->saleType,
                ...$rates->toAttributes(),
                /** Nada cobrado todavía: el saldo lo fija `refreshTotals`. */
                'paid_amount' => 0,
                'payment_status' => 'pending',
                'notes' => $command->notes,
                'status' => 'draft',
                'created_by' => $command->createdBy,
            ]);

            $this->syncLines($invoice, $command->lines);
            $this->refreshTotals($invoice, $rates->amountDecimals);
        });
    }

    public function findById(string $id, ?string $companyId = null): ?SalesInvoice
    {
        return SalesInvoice::query()
            ->with(self::DETAIL_RELATIONS)
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->find($id);
    }

    public function findOrFail(string $id, ?string $companyId = null): SalesInvoice
    {
        return SalesInvoice::query()
            ->with(self::DETAIL_RELATIONS)
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->findOrFail($id);
    }

    public function update(SalesInvoice $model, UpdateSalesInvoiceCommand $command, DocumentRatesData $rates): void
    {
        DB::transaction(function () use ($model, $command, $rates): void {
            /** El estado, el correlativo fiscal y lo cobrado quedan fuera: no se capturan. */
            $model->update([
                'client_id' => $command->clientId,
                'sourceable_type' => $command->sourceableType,
                'sourceable_id' => $command->sourceableId,
                'dispatch_id' => $command->dispatchId,
                'client_address_id' => $command->clientAddressId,
                'warehouse_id' => $command->warehouseId,
                'salesperson_id' => $command->salespersonId,
                'invoice_series' => $command->invoiceSeries,
                'invoice_date' => $command->invoiceDate,
                'due_date' => $command->dueDate,
                'sale_type' => $command->saleType,
                ...$rates->toAttributes(),
                'notes' => $command->notes,
            ]);

            $this->syncLines($model, $command->lines);
            $this->refreshTotals($model, $rates->amountDecimals);
        });
    }

    public function updateStatus(SalesInvoice $model, UpdateStatusSalesInvoiceCommand $command): void
    {
        DB::transaction(function () use ($model, $command): void {
            if ($command->status === 'confirmed') {
                $this->confirm($model);

                return;
            }

            if ($command->status === 'cancelled') {
                $this->cancel($model, $command->cancellationReason);

                return;
            }

            $model->update(['status' => $command->status]);
        });
    }

    /**
     * @return array<int, SalesInvoiceLine>
     */
    public function activeLines(SalesInvoice $model): array
    {
        return SalesInvoiceLine::query()
            ->with(['item'])
            ->where('sales_invoice_id', $model->id)
            ->where('status', 'active')
            ->orderBy('line_number')
            ->get()
            ->all();
    }

    public function writeLineCost(SalesInvoiceLine $line, WriteSalesInvoiceLineCostCommand $command): SalesInvoiceLine
    {
        $line->update([
            'unit_cost' => $command->unitCost,
            'total_cost' => $command->totalCost,
            'margin_amount' => $command->marginAmount,
        ]);

        return $line;
    }

    /** El costo de la mercancía vendida de toda la factura. */
    public function writeTotalCost(SalesInvoice $model, float $totalCost): SalesInvoice
    {
        $model->update(['total_cost' => $totalCost]);

        return $model;
    }

    public function markOverdue(string $onDate, ?string $companyId = null): int
    {
        return SalesInvoice::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereIn('status', SalesInvoice::COLLECTIBLE_STATUSES)
            ->whereIn('payment_status', ['pending', 'partial'])
            ->where('balance', '>', 0)
            ->whereDate('due_date', '<', $onDate)
            ->update(['payment_status' => 'overdue']);
    }

    public function lockById(string $id, ?string $companyId = null): ?SalesInvoice
    {
        return SalesInvoice::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->lockForUpdate()
            ->find($id);
    }

    public function writeCollection(SalesInvoice $model, WriteSalesInvoiceCollectionCommand $command): SalesInvoice
    {
        $model->update([
            'paid_amount' => $command->paidAmount,
            'balance' => $command->balance,
            'payment_status' => $command->paymentStatus,
        ]);

        return $model;
    }

    public function writeSettledStatus(SalesInvoice $model, string $status): void
    {
        $model->update(['status' => $status]);
    }

    public function lockLineById(string $id, ?string $companyId = null): ?SalesInvoiceLine
    {
        return SalesInvoiceLine::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->lockForUpdate()
            ->find($id);
    }

    public function writeLineReturn(
        SalesInvoiceLine $line,
        WriteSalesInvoiceLineReturnCommand $command,
    ): SalesInvoiceLine {
        $line->update(['returned_quantity' => $command->returnedQuantity]);

        return $line;
    }

    /**
     * @return array{ data: SalesInvoice[], total: int }
     */
    public function search(SearchSalesInvoiceCommand $command): array
    {
        $query = SalesInvoice::query()
            ->with(['client', 'warehouse', 'salesperson'])
            ->when($command->companyId, fn ($q) => $q->where('company_id', $command->companyId));

        $query = $this->apply($query, $command->filters);

        $total = $query->count();

        $data = $query->orderByDesc('invoice_date')
            ->orderByDesc('created_at')
            ->limit($command->limit)
            ->offset($command->offset)
            ->get();

        return ['data' => $data->all(), 'total' => $total];
    }

    /**
     * Emitir la factura: quema el correlativo fiscal, congela el costo de la
     * mercancía vendida, carga la cuenta por cobrar del cliente y avanza el
     * pedido que la originó.
     *
     * La factura no mueve existencia: eso lo hizo el despacho.
     */
    private function confirm(SalesInvoice $invoice): void
    {
        $invoice->update([
            'status' => 'confirmed',
            'invoice_number' => $invoice->invoice_number
                ?? $this->generateNextInvoiceNumber($invoice),
        ]);

        $this->freezeCosts($invoice);
        $this->applyInvoicedQuantities($invoice, 1);

        $invoice->update(['payment_status' => $this->paymentStatusOf($invoice)]);

        $this->moveClientBalance($invoice, (float) $invoice->balance);
    }

    /**
     * Anular: revierte lo que hizo la emisión. Un borrador no llegó a asentar
     * nada, así que solo cambia de estado.
     */
    private function cancel(SalesInvoice $invoice, ?string $reason): void
    {
        $wasPosted = $invoice->status !== 'draft';

        if ($wasPosted) {
            $this->applyInvoicedQuantities($invoice, -1);
            $this->moveClientBalance($invoice, -(float) $invoice->balance);
        }

        $invoice->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);
    }

    /**
     * Alinea `app_sales_invoice_lines` con lo enviado desde la pantalla.
     *
     * Las filas existentes se reconocen por su `id`; las que dejan de venir no
     * se borran, se desactivan (política de no borrado). Un `id` que no
     * pertenece a esta factura se ignora y la fila entra como nueva.
     *
     * @param  array<int, SalesInvoiceLineData>  $lines
     */
    private function syncLines(SalesInvoice $invoice, array $lines): void
    {
        $existing = SalesInvoiceLine::query()
            ->where('sales_invoice_id', $invoice->id)
            ->get()
            ->keyBy('id');

        /**
         * `unique(sales_invoice_id, line_number)` impide renumerar en sitio:
         * primero se aparta el rango con números negativos y luego se reasigna
         * 1..N.
         */
        $parked = 0;

        foreach ($existing as $line) {
            $line->update(['line_number' => --$parked]);
        }

        $factors = $this->conversionFactors($invoice->company_id, $lines);

        $keep = [];
        $lineNumber = 0;

        foreach ($lines as $line) {
            $attributes = [
                ...$this->lineAmounts($line, $factors),
                'company_id' => $invoice->company_id,
                'line_number' => ++$lineNumber,
                'sourceable_type' => $line->sourceableType,
                'sourceable_id' => $line->sourceableId,
                'item_id' => $line->itemId,
                'measurement_unit_id' => $line->measurementUnitId,
                'warehouse_id' => $line->warehouseId,
                'lot_id' => $line->lotId,
                'serial_id' => $line->serialId,
                'tax_id' => $line->taxId,
                'notes' => $line->notes,
                'status' => $line->status,
            ];

            if ($line->id !== null && $existing->has($line->id)) {
                $existing->get($line->id)->update($attributes);
                $keep[] = $line->id;

                continue;
            }

            $keep[] = SalesInvoiceLine::create([
                ...$attributes,
                'sales_invoice_id' => $invoice->id,
            ])->id;
        }

        $this->deactivateMissing($invoice, $keep, $lineNumber);
    }

    /**
     * Importes de una línea. Se calculan aquí y nunca se aceptan del cliente:
     * el precio y el descuento quedan congelados en la línea.
     *
     * El costo no entra: se congela al confirmar, con el costo vigente del
     * artículo en ese momento.
     *
     * @param  array<string, string>  $factors
     * @return array<string, string|float>
     */
    private function lineAmounts(SalesInvoiceLineData $line, array $factors): array
    {
        $quantity = (float) $line->quantity;
        $unitPrice = (float) $line->unitPrice;
        $discountPercent = (float) $line->discountPercent;
        $taxPercent = (float) $line->taxPercent;
        $withholdingPercent = (float) $line->withholdingPercent;

        $gross = round($quantity * $unitPrice, 2);
        $discountAmount = round($gross * $discountPercent / 100, 2);
        $subtotal = round($gross - $discountAmount, 2);
        $taxAmount = round($subtotal * $taxPercent / 100, 2);

        $factor = (float) ($factors[$line->itemId.':'.$line->measurementUnitId] ?? 1);

        return [
            'quantity' => $line->quantity,
            'base_quantity' => round($quantity * $factor, 4),
            'unit_price' => $line->unitPrice,
            'discount_percent' => $line->discountPercent,
            'discount_amount' => $discountAmount,
            'tax_percent' => $line->taxPercent,
            'tax_amount' => $taxAmount,
            'withholding_percent' => $line->withholdingPercent,
            /** La retención se practica sobre el impuesto, no sobre la base. */
            'withholding_amount' => round($taxAmount * $withholdingPercent / 100, 2),
            'subtotal' => $subtotal,
            'total' => round($subtotal + $taxAmount, 2),
        ];
    }

    /**
     * Factores de conversión a la unidad base de cada artículo, indexados por
     * `item_id:measurement_unit_id`. Una unidad no registrada convierte 1 a 1.
     *
     * @param  array<int, SalesInvoiceLineData>  $lines
     * @return array<string, string>
     */
    private function conversionFactors(?string $companyId, array $lines): array
    {
        if ($lines === []) {
            return [];
        }

        return ItemUnit::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereIn('item_id', array_map(fn (SalesInvoiceLineData $line): string => $line->itemId, $lines))
            ->get()
            ->mapWithKeys(fn (ItemUnit $unit): array => [
                $unit->item_id.':'.$unit->measurement_unit_id => (string) $unit->conversion_factor,
            ])
            ->all();
    }

    /**
     * Desactiva las líneas que ya no vienen en el formulario y las manda al
     * final de la numeración, para que las activas conserven el orden 1..N.
     *
     * @param  array<int, string>  $keep
     */
    private function deactivateMissing(SalesInvoice $invoice, array $keep, int $lastLineNumber): void
    {
        $dropped = SalesInvoiceLine::query()
            ->where('sales_invoice_id', $invoice->id)
            ->when($keep !== [], fn ($q) => $q->whereNotIn('id', $keep))
            ->orderBy('line_number')
            ->get();

        foreach ($dropped as $line) {
            $line->update([
                'line_number' => ++$lastLineNumber,
                'status' => 'inactive',
            ]);
        }
    }

    /**
     * Los totales de la factura suman solo las líneas activas.
     *
     * `total` agrega el flete cobrado. La retención no se descuenta del total:
     * es un impuesto que el cliente entera al fisco en nombre de la empresa, y
     * se salda con su comprobante, no con la factura.
     *
     * Los importes en bolívares se congelan aquí porque la factura tiene valor
     * legal (docs/monedas.md §4).
     */
    private function refreshTotals(SalesInvoice $invoice, int $amountDecimals): void
    {
        $lines = SalesInvoiceLine::query()
            ->where('sales_invoice_id', $invoice->id)
            ->where('status', 'active')
            ->get();

        $subtotal = round((float) $lines->sum('subtotal'), 2);
        $taxAmount = round((float) $lines->sum('tax_amount'), 2);
        $total = round($subtotal + $taxAmount, 2);
        $rate = (float) $invoice->exchange_rate;

        $invoice->update([
            'subtotal' => $subtotal,
            'discount_amount' => round((float) $lines->sum('discount_amount'), 2),
            'tax_amount' => $taxAmount,
            'withholding_amount' => round((float) $lines->sum('withholding_amount'), 2),
            'total' => $total,
            'subtotal_ves' => round($subtotal * $rate, $amountDecimals),
            'tax_amount_ves' => round($taxAmount * $rate, $amountDecimals),
            'total_ves' => round($total * $rate, $amountDecimals),
            'balance' => round($total - (float) $invoice->paid_amount, 2),
        ]);
    }

    /**
     * Costo provisional de la mercancía vendida, puesto al confirmar con el
     * costo que el artículo tiene guardado.
     *
     * Es solo el punto de partida: si la mercancía salió con un despacho,
     * `SalesInvoicePostingService` vuelve a escribirlo con el costo real del
     * movimiento. Una factura sin despacho se queda con este.
     */
    private function freezeCosts(SalesInvoice $invoice): void
    {
        $lines = SalesInvoiceLine::query()
            ->where('sales_invoice_id', $invoice->id)
            ->where('status', 'active')
            ->get();

        if ($lines->isEmpty()) {
            return;
        }

        $costs = Item::query()
            ->whereIn('id', $lines->pluck('item_id')->unique()->all())
            ->get()
            ->mapWithKeys(fn (Item $item): array => [
                $item->id => (float) ($item->cost_method === 'standard'
                    ? $item->standard_cost
                    : $item->average_cost),
            ])
            ->all();

        $totalCost = 0.0;

        foreach ($lines as $line) {
            $unitCost = $costs[$line->item_id] ?? 0.0;
            $lineCost = round((float) $line->base_quantity * $unitCost, 2);
            $totalCost += $lineCost;

            $line->update([
                'unit_cost' => $unitCost,
                'total_cost' => $lineCost,
                'margin_amount' => round((float) $line->subtotal - $lineCost, 2),
            ]);
        }

        $invoice->update(['total_cost' => round($totalCost, 2)]);
    }

    /**
     * Mueve `invoiced_quantity` de las líneas del pedido origen y recalcula su
     * avance de facturación. `$sign` vale 1 al emitir y -1 al anular.
     */
    private function applyInvoicedQuantities(SalesInvoice $invoice, int $sign): void
    {
        $lines = SalesInvoiceLine::query()
            ->where('sales_invoice_id', $invoice->id)
            ->where('status', 'active')
            ->where('sourceable_type', SalesOrderLine::MORPH_ALIAS)
            ->whereNotNull('sourceable_id')
            ->get();

        if ($lines->isEmpty()) {
            return;
        }

        $orderLines = SalesOrderLine::query()
            ->whereIn('id', $lines->pluck('sourceable_id')->unique()->all())
            ->get()
            ->keyBy('id');

        foreach ($lines as $line) {
            $orderLine = $orderLines->get($line->sourceable_id);

            if ($orderLine === null) {
                continue;
            }

            $invoiced = (float) $orderLine->invoiced_quantity + $sign * (float) $line->quantity;

            $orderLine->update(['invoiced_quantity' => max(round($invoiced, 4), 0)]);
        }

        $this->refreshInvoicedPercent($orderLines->pluck('sales_order_id')->unique()->all());
    }

    /**
     * @param  array<int, string>  $orderIds
     */
    private function refreshInvoicedPercent(array $orderIds): void
    {
        foreach (SalesOrder::query()->whereIn('id', $orderIds)->get() as $order) {
            $lines = SalesOrderLine::query()
                ->where('sales_order_id', $order->id)
                ->where('status', 'active')
                ->get();

            $ordered = (float) $lines->sum('quantity');

            $order->update([
                'invoiced_percent' => $ordered > 0
                    ? min(round((float) $lines->sum('invoiced_quantity') * 100 / $ordered, 4), 100)
                    : 0,
            ]);
        }
    }

    /**
     * Carga (o descarga) la cuenta por cobrar del cliente.
     */
    private function moveClientBalance(SalesInvoice $invoice, float $amount): void
    {
        $client = Client::query()->find($invoice->client_id);

        if ($client === null) {
            return;
        }

        $client->update([
            'current_balance' => round((float) $client->current_balance + $amount, 2),
        ]);
    }

    /**
     * Una factura sin saldo ya está pagada; con saldo, vencida si pasó su
     * fecha de vencimiento.
     */
    private function paymentStatusOf(SalesInvoice $invoice): string
    {
        $balance = (float) $invoice->balance;

        if ($balance <= 0) {
            return 'paid';
        }

        /** Vence al terminar el día: una factura que vence hoy no está vencida. */
        $expired = $invoice->due_date->lt(now()->startOfDay());

        if ((float) $invoice->paid_amount > 0) {
            return $expired ? 'overdue' : 'partial';
        }

        return $expired ? 'overdue' : 'pending';
    }

    /**
     * Generate the next sequential per-company code (FVE000001, FVE000002, …).
     */
    private function generateNextCode(string $companyId): string
    {
        $last = SalesInvoice::query()
            ->where('company_id', $companyId)
            ->where('code', 'like', SalesInvoice::CODE_PREFIX.'%')
            ->lockForUpdate()
            ->orderByDesc('code')
            ->value('code');

        $next = $last !== null
            ? ((int) substr($last, strlen(SalesInvoice::CODE_PREFIX))) + 1
            : 1;

        return SalesInvoice::CODE_PREFIX.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Correlativo fiscal siguiente dentro de la serie. Es independiente del
     * `code`: la serie la autoriza el fisco y cada una lleva su propia cuenta.
     */
    private function generateNextInvoiceNumber(SalesInvoice $invoice): string
    {
        $last = SalesInvoice::query()
            ->where('company_id', $invoice->company_id)
            ->when(
                $invoice->invoice_series === null,
                fn ($q) => $q->whereNull('invoice_series'),
                fn ($q) => $q->where('invoice_series', $invoice->invoice_series),
            )
            ->whereNotNull('invoice_number')
            ->lockForUpdate()
            ->orderByDesc('invoice_number')
            ->value('invoice_number');

        $next = $last !== null ? ((int) $last) + 1 : 1;

        return str_pad((string) $next, self::INVOICE_NUMBER_LENGTH, '0', STR_PAD_LEFT);
    }
}
