<?php

declare(strict_types=1);

namespace App\Modules\SalesOrder\Repositories;

use App\Modules\Item\Models\ItemUnit;
use App\Modules\SalesOrder\Commands\CreateSalesOrderCommand;
use App\Modules\SalesOrder\Commands\SalesOrderLineData;
use App\Modules\SalesOrder\Commands\SearchSalesOrderCommand;
use App\Modules\SalesOrder\Commands\UpdateSalesOrderCommand;
use App\Modules\SalesOrder\Commands\UpdateStatusSalesOrderCommand;
use App\Modules\SalesOrder\Models\SalesOrder;
use App\Modules\SalesOrder\Models\SalesOrderLine;
use App\Modules\SalesOrder\Repositories\Contracts\SalesOrderRepositoryInterface;
use Illuminate\Support\Facades\DB;

class SalesOrderRepository extends SalesOrderFilters implements SalesOrderRepositoryInterface
{
    /** Relaciones que necesita la pantalla de detalle y el formulario de edición. */
    private const DETAIL_RELATIONS = [
        'lines.item',
        'lines.measurementUnit',
        'client',
        'clientAddress',
        'warehouse',
        'priceList',
        'salesperson',
    ];

    public function create(CreateSalesOrderCommand $command): void
    {
        DB::transaction(function () use ($command): void {
            $order = SalesOrder::create([
                'id' => $command->id,
                'company_id' => $command->companyId,
                'code' => $this->generateNextCode($command->companyId),
                'client_id' => $command->clientId,
                'client_address_id' => $command->clientAddressId,
                'warehouse_id' => $command->warehouseId,
                'price_list_id' => $command->priceListId,
                'salesperson_id' => $command->salespersonId,
                'order_date' => $command->orderDate,
                'expected_date' => $command->expectedDate,
                'client_reference' => $command->clientReference,
                'currency' => $command->currency,
                'exchange_rate' => $command->exchangeRate,
                'payment_term_days' => $command->paymentTermDays,
                /** Los avances nacen en cero: los mueven el despacho y la factura. */
                'dispatched_percent' => 0,
                'invoiced_percent' => 0,
                'notes' => $command->notes,
                'status' => 'draft',
                'created_by' => $command->createdBy,
            ]);

            $this->syncLines($order, $command->lines);
            $this->refreshTotals($order);
        });
    }

    public function findById(string $id, ?string $companyId = null): ?SalesOrder
    {
        return SalesOrder::query()
            ->with(self::DETAIL_RELATIONS)
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->find($id);
    }

    public function findOrFail(string $id, ?string $companyId = null): SalesOrder
    {
        return SalesOrder::query()
            ->with(self::DETAIL_RELATIONS)
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->findOrFail($id);
    }

    public function update(SalesOrder $model, UpdateSalesOrderCommand $command): void
    {
        DB::transaction(function () use ($model, $command): void {
            /** Los avances y el estado quedan fuera: no se capturan desde el formulario. */
            $model->update([
                'client_id' => $command->clientId,
                'client_address_id' => $command->clientAddressId,
                'warehouse_id' => $command->warehouseId,
                'price_list_id' => $command->priceListId,
                'salesperson_id' => $command->salespersonId,
                'order_date' => $command->orderDate,
                'expected_date' => $command->expectedDate,
                'client_reference' => $command->clientReference,
                'currency' => $command->currency,
                'exchange_rate' => $command->exchangeRate,
                'payment_term_days' => $command->paymentTermDays,
                'notes' => $command->notes,
            ]);

            $this->syncLines($model, $command->lines);
            $this->refreshTotals($model);
        });
    }

    public function updateStatus(SalesOrder $model, UpdateStatusSalesOrderCommand $command): void
    {
        $attributes = ['status' => $command->status];

        if ($command->status === 'confirmed') {
            $attributes['approved_by'] = $command->approvedBy;
            $attributes['approved_at'] = now();
        }

        if ($command->status === 'cancelled') {
            $attributes['cancelled_at'] = now();
            $attributes['cancellation_reason'] = $command->cancellationReason;

            /** Anular libera la reserva: el stock vuelve a estar disponible. */
            SalesOrderLine::query()
                ->where('sales_order_id', $model->id)
                ->update(['reserved_quantity' => 0]);
        }

        $model->update($attributes);
    }

    /**
     * @return array{ data: SalesOrder[], total: int }
     */
    public function search(SearchSalesOrderCommand $command): array
    {
        $query = SalesOrder::query()
            ->with(['client', 'warehouse', 'salesperson'])
            ->when($command->companyId, fn ($q) => $q->where('company_id', $command->companyId));

        $query = $this->apply($query, $command->filters);

        $total = $query->count();

        $data = $query->orderByDesc('order_date')
            ->orderByDesc('created_at')
            ->limit($command->limit)
            ->offset($command->offset)
            ->get();

        return ['data' => $data->all(), 'total' => $total];
    }

    /**
     * Alinea `app_sales_order_lines` con lo enviado desde la pantalla del pedido.
     *
     * Las filas existentes se reconocen por su `id`; las que dejan de venir no se
     * borran, se desactivan (política de no borrado). Un `id` que no pertenece a
     * este pedido se ignora y la fila entra como nueva.
     *
     * @param  array<int, SalesOrderLineData>  $lines
     */
    private function syncLines(SalesOrder $order, array $lines): void
    {
        $existing = SalesOrderLine::query()
            ->where('sales_order_id', $order->id)
            ->get()
            ->keyBy('id');

        /**
         * `unique(sales_order_id, line_number)` impide renumerar en sitio: primero
         * se aparta el rango con números negativos y luego se reasigna 1..N.
         */
        $parked = 0;

        foreach ($existing as $line) {
            $line->update(['line_number' => --$parked]);
        }

        $factors = $this->conversionFactors($order->company_id, $lines);

        $keep = [];
        $lineNumber = 0;

        foreach ($lines as $line) {
            $attributes = [
                ...$this->lineAmounts($line, $factors),
                'company_id' => $order->company_id,
                'line_number' => ++$lineNumber,
                'item_id' => $line->itemId,
                'measurement_unit_id' => $line->measurementUnitId,
                'tax_id' => $line->taxId,
                'notes' => $line->notes,
                'status' => $line->status,
            ];

            if ($line->id !== null && $existing->has($line->id)) {
                $existing->get($line->id)->update($attributes);
                $keep[] = $line->id;

                continue;
            }

            $keep[] = SalesOrderLine::create([
                ...$attributes,
                'sales_order_id' => $order->id,
            ])->id;
        }

        $this->deactivateMissing($order, $keep, $lineNumber);
    }

    /**
     * Importes de una línea. Se calculan aquí y nunca se aceptan del cliente:
     * el precio y el descuento quedan congelados en la línea.
     *
     * @param  array<string, string>  $factors
     * @return array<string, string|float>
     */
    private function lineAmounts(SalesOrderLineData $line, array $factors): array
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
        $withholdingAmount = round($subtotal * $withholdingPercent / 100, 2);

        $factor = (float) ($factors[$line->itemId.':'.$line->measurementUnitId] ?? 1);

        return [
            'quantity' => $line->quantity,
            'base_quantity' => round($quantity * $factor, 4),
            'unit_price' => $line->unitPrice,
            'list_price' => $line->listPrice,
            'discount_percent' => $line->discountPercent,
            'discount_amount' => $discountAmount,
            'tax_percent' => $line->taxPercent,
            'tax_amount' => $taxAmount,
            'withholding_percent' => $line->withholdingPercent,
            'withholding_amount' => $withholdingAmount,
            'subtotal' => $subtotal,
            'total' => round($subtotal + $taxAmount, 2),
            /** Nada se ha despachado todavía: todo el pedido está pendiente. */
            'pending_quantity' => $line->quantity,
        ];
    }

    /**
     * Factores de conversión a la unidad base de cada artículo, indexados por
     * `item_id:measurement_unit_id`. Una unidad no registrada convierte 1 a 1.
     *
     * @param  array<int, SalesOrderLineData>  $lines
     * @return array<string, string>
     */
    private function conversionFactors(?string $companyId, array $lines): array
    {
        if ($lines === []) {
            return [];
        }

        return ItemUnit::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereIn('item_id', array_map(fn (SalesOrderLineData $line): string => $line->itemId, $lines))
            ->get()
            ->mapWithKeys(fn (ItemUnit $unit): array => [
                $unit->item_id.':'.$unit->measurement_unit_id => (string) $unit->conversion_factor,
            ])
            ->all();
    }

    /**
     * Desactiva las líneas que ya no vienen en el formulario y las manda al final
     * de la numeración, para que las activas conserven el orden 1..N.
     *
     * @param  array<int, string>  $keep
     */
    private function deactivateMissing(SalesOrder $order, array $keep, int $lastLineNumber): void
    {
        $dropped = SalesOrderLine::query()
            ->where('sales_order_id', $order->id)
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
     * Los totales del pedido suman solo las líneas activas.
     */
    private function refreshTotals(SalesOrder $order): void
    {
        $lines = SalesOrderLine::query()
            ->where('sales_order_id', $order->id)
            ->where('status', 'active')
            ->get();

        $subtotal = round((float) $lines->sum('subtotal'), 2);
        $taxAmount = round((float) $lines->sum('tax_amount'), 2);

        $order->update([
            'subtotal' => $subtotal,
            'discount_amount' => round((float) $lines->sum('discount_amount'), 2),
            'tax_amount' => $taxAmount,
            'total' => round($subtotal + $taxAmount, 2),
        ]);
    }

    /**
     * Generate the next sequential per-company code (OVE000001, OVE000002, …).
     */
    private function generateNextCode(string $companyId): string
    {
        $last = SalesOrder::query()
            ->where('company_id', $companyId)
            ->where('code', 'like', SalesOrder::CODE_PREFIX.'%')
            ->lockForUpdate()
            ->orderByDesc('code')
            ->value('code');

        $next = $last !== null
            ? ((int) substr($last, strlen(SalesOrder::CODE_PREFIX))) + 1
            : 1;

        return SalesOrder::CODE_PREFIX.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
