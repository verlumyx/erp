<?php

declare(strict_types=1);

namespace App\Modules\PurchaseOrder\Repositories;

use App\Modules\Item\Models\Item;
use App\Modules\Item\Models\ItemUnit;
use App\Modules\Item\Repositories\Contracts\ItemRepositoryInterface;
use App\Modules\PurchaseOrder\Commands\CreatePurchaseOrderCommand;
use App\Modules\PurchaseOrder\Commands\PurchaseOrderLineData;
use App\Modules\PurchaseOrder\Commands\SearchPurchaseOrderCommand;
use App\Modules\PurchaseOrder\Commands\UpdatePurchaseOrderCommand;
use App\Modules\PurchaseOrder\Commands\UpdateStatusPurchaseOrderCommand;
use App\Modules\PurchaseOrder\Models\PurchaseOrder;
use App\Modules\PurchaseOrder\Models\PurchaseOrderLine;
use App\Modules\PurchaseOrder\Repositories\Contracts\PurchaseOrderRepositoryInterface;
use Illuminate\Support\Facades\DB;

class PurchaseOrderRepository extends PurchaseOrderFilters implements PurchaseOrderRepositoryInterface
{
    public function __construct(
        private readonly ItemRepositoryInterface $items,
    ) {}

    public function create(CreatePurchaseOrderCommand $command): void
    {
        DB::transaction(function () use ($command): void {
            $order = PurchaseOrder::create([
                'id' => $command->id,
                'company_id' => $command->companyId,
                'code' => $this->generateNextCode($command->companyId),
                'supplier_id' => $command->supplierId,
                'warehouse_id' => $command->warehouseId,
                'order_date' => $command->orderDate,
                'expected_date' => $command->expectedDate,
                'supplier_reference' => $command->supplierReference,
                'currency' => $command->currency,
                'exchange_rate' => $command->exchangeRate,
                'payment_term_days' => $command->paymentTermDays,
                ...$this->totals($command->lines, $command->discountAmount),
                /** Los avances nacen en cero: los mueven Entradas y Facturas de compra. */
                'received_percent' => 0,
                'invoiced_percent' => 0,
                'notes' => $command->notes,
                'status' => 'draft',
                'created_by' => $command->createdBy,
            ]);

            $this->syncLines($order, $command->lines);
        });
    }

    public function findById(string $id, ?string $companyId = null): ?PurchaseOrder
    {
        return PurchaseOrder::query()
            ->with($this->detailRelations())
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->find($id);
    }

    public function findOrFail(string $id, ?string $companyId = null): PurchaseOrder
    {
        return PurchaseOrder::query()
            ->with($this->detailRelations())
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->findOrFail($id);
    }

    public function update(PurchaseOrder $model, UpdatePurchaseOrderCommand $command): void
    {
        DB::transaction(function () use ($model, $command): void {
            /** Los avances y las marcas de aprobación/anulación no se editan aquí. */
            $model->update([
                'supplier_id' => $command->supplierId,
                'warehouse_id' => $command->warehouseId,
                'order_date' => $command->orderDate,
                'expected_date' => $command->expectedDate,
                'supplier_reference' => $command->supplierReference,
                'currency' => $command->currency,
                'exchange_rate' => $command->exchangeRate,
                'payment_term_days' => $command->paymentTermDays,
                ...$this->totals($command->lines, $command->discountAmount),
                'notes' => $command->notes,
            ]);

            $this->syncLines($model, $command->lines);
        });
    }

    public function updateStatus(PurchaseOrder $model, UpdateStatusPurchaseOrderCommand $command): void
    {
        $attributes = ['status' => $command->status];

        if ($command->status === 'confirmed') {
            $attributes['approved_by'] = $command->approvedBy;
            $attributes['approved_at'] = now();
        }

        if ($command->status === 'cancelled') {
            $attributes['cancelled_at'] = now();
            $attributes['cancellation_reason'] = $command->cancellationReason;
        }

        $model->update($attributes);
    }

    /**
     * @return array{ data: PurchaseOrder[], total: int }
     */
    public function search(SearchPurchaseOrderCommand $command): array
    {
        $query = PurchaseOrder::query()
            ->with(['supplier', 'warehouse'])
            ->when($command->companyId, fn ($q) => $q->where('company_id', $command->companyId));

        $query = $this->apply($query, $command->filters);

        $total = $query->count();

        $data = $query->orderByDesc('order_date')
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
        return ['supplier', 'warehouse', 'lines.item', 'lines.measurementUnit'];
    }

    /**
     * Alinea `app_purchase_order_lines` con lo enviado desde la pantalla.
     *
     * Las filas existentes se reconocen por su `id` y conservan su `line_number`;
     * las nuevas toman el siguiente número libre. Las que dejan de venir no se
     * borran, se desactivan (política de no borrado), y por eso los números no
     * se recalculan: el par `(orden, line_number)` es único y una fila inactiva
     * sigue ocupando el suyo.
     *
     * @param  array<int, PurchaseOrderLineData>  $lines
     */
    private function syncLines(PurchaseOrder $order, array $lines): void
    {
        $existing = PurchaseOrderLine::query()
            ->where('purchase_order_id', $order->id)
            ->get()
            ->keyBy('id');

        $factors = $this->conversionFactors($order->company_id, $lines);
        $nextNumber = (int) $existing->max('line_number');
        $keep = [];

        foreach ($lines as $line) {
            $current = $line->id !== null ? $existing->get($line->id) : null;

            /** Lo ya recibido pertenece al histórico de la línea: nunca se pisa. */
            $received = (float) ($current->received_quantity ?? 0);
            $factor = $factors[$line->itemId.'|'.$line->measurementUnitId] ?? 1.0;

            $attributes = [
                'company_id' => $order->company_id,
                'item_id' => $line->itemId,
                'measurement_unit_id' => $line->measurementUnitId,
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
                'pending_quantity' => max($line->quantity - $received, 0),
                'status' => $line->status,
                'notes' => $line->notes,
            ];

            if ($current !== null) {
                $current->update($attributes);
                $keep[] = $current->id;

                continue;
            }

            $keep[] = PurchaseOrderLine::create([
                ...$attributes,
                'purchase_order_id' => $order->id,
                'line_number' => ++$nextNumber,
            ])->id;
        }

        PurchaseOrderLine::query()
            ->where('purchase_order_id', $order->id)
            ->when($keep !== [], fn ($q) => $q->whereNotIn('id', $keep))
            ->update(['status' => 'inactive']);
    }

    /**
     * Factor de conversión a la unidad base por par artículo/unidad.
     *
     * Se resuelve a través del repositorio de artículos: el módulo de órdenes
     * nunca consulta las tablas del módulo de inventario directamente. Un par
     * sin unidad registrada cae en 1, que es lo que valida el Request.
     *
     * @param  array<int, PurchaseOrderLineData>  $lines
     * @return array<string, float>
     */
    private function conversionFactors(?string $companyId, array $lines): array
    {
        $factors = [];

        foreach (array_unique(array_map(fn (PurchaseOrderLineData $line): string => $line->itemId, $lines)) as $itemId) {
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
     * Totales de la cabecera. Suman **solo** las líneas activas; el descuento
     * global se resta después del subtotal, como cualquier rebaja de documento.
     *
     * @param  array<int, PurchaseOrderLineData>  $lines
     * @return array{ subtotal: float, discount_amount: float, tax_amount: float, total: float }
     */
    private function totals(array $lines, float $discountAmount): array
    {
        $active = array_filter($lines, fn (PurchaseOrderLineData $line): bool => $line->status === 'active');

        $subtotal = round(array_sum(array_map(fn (PurchaseOrderLineData $line): float => $line->subtotal, $active)), 2);
        $tax = round(array_sum(array_map(fn (PurchaseOrderLineData $line): float => $line->taxAmount, $active)), 2);
        $discount = round($discountAmount, 2);

        return [
            'subtotal' => $subtotal,
            'discount_amount' => $discount,
            'tax_amount' => $tax,
            'total' => round($subtotal - $discount + $tax, 2),
        ];
    }

    /**
     * Generate the next sequential per-company code (OCO000001, OCO000002, …).
     */
    private function generateNextCode(string $companyId): string
    {
        $last = PurchaseOrder::query()
            ->where('company_id', $companyId)
            ->where('code', 'like', PurchaseOrder::CODE_PREFIX.'%')
            ->lockForUpdate()
            ->orderByDesc('code')
            ->value('code');

        $next = $last !== null
            ? ((int) substr($last, strlen(PurchaseOrder::CODE_PREFIX))) + 1
            : 1;

        return PurchaseOrder::CODE_PREFIX.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
