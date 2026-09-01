<?php

declare(strict_types=1);

namespace App\Modules\Transfer\Repositories;

use App\Modules\Item\Models\Item;
use App\Modules\Item\Models\ItemUnit;
use App\Modules\Item\Repositories\Contracts\ItemRepositoryInterface;
use App\Modules\Transfer\Commands\CreateTransferCommand;
use App\Modules\Transfer\Commands\SearchTransferCommand;
use App\Modules\Transfer\Commands\TransferLineData;
use App\Modules\Transfer\Commands\UpdateStatusTransferCommand;
use App\Modules\Transfer\Commands\UpdateTransferCommand;
use App\Modules\Transfer\Commands\WriteTransferLineCostCommand;
use App\Modules\Transfer\Commands\WriteTransferReceiptCommand;
use App\Modules\Transfer\Models\Transfer;
use App\Modules\Transfer\Models\TransferLine;
use App\Modules\Transfer\Repositories\Contracts\TransferRepositoryInterface;
use Illuminate\Support\Facades\DB;

class TransferRepository extends TransferFilters implements TransferRepositoryInterface
{
    public function __construct(
        private readonly ItemRepositoryInterface $items,
    ) {}

    /**
     * @param  array<int, float>  $unitCosts
     */
    public function create(CreateTransferCommand $command, array $unitCosts): void
    {
        DB::transaction(function () use ($command, $unitCosts): void {
            $transfer = Transfer::create([
                'id' => $command->id,
                'company_id' => $command->companyId,
                'code' => $this->generateNextCode($command->companyId),
                'origin_warehouse_id' => $command->originWarehouseId,
                'destination_warehouse_id' => $command->destinationWarehouseId,
                'transit_warehouse_id' => $command->transitWarehouseId,
                'transfer_date' => $command->transferDate,
                'expected_date' => $command->expectedDate,
                'reason' => $command->reason,
                'reason_detail' => $command->reasonDetail,
                'driver_id' => $command->driverId,
                'vehicle_plate' => $command->vehiclePlate,
                'route_id' => $command->routeId,
                'transfer_status' => 'pending',
                'notes' => $command->notes,
                'status' => 'draft',
                'created_by' => $command->createdBy,
            ]);

            $this->syncLines($transfer, $command->lines, $unitCosts);
            $this->refreshTotals($transfer);
        });
    }

    public function findById(string $id, ?string $companyId = null): ?Transfer
    {
        return Transfer::query()
            ->with($this->detailRelations())
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->find($id);
    }

    public function findOrFail(string $id, ?string $companyId = null): Transfer
    {
        return Transfer::query()
            ->with($this->detailRelations())
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->findOrFail($id);
    }

    /**
     * @param  array<int, float>  $unitCosts
     */
    public function update(Transfer $model, UpdateTransferCommand $command, array $unitCosts): void
    {
        DB::transaction(function () use ($model, $command, $unitCosts): void {
            /** La llegada y la marca de anulación no se editan aquí. */
            $model->update([
                'origin_warehouse_id' => $command->originWarehouseId,
                'destination_warehouse_id' => $command->destinationWarehouseId,
                'transit_warehouse_id' => $command->transitWarehouseId,
                'transfer_date' => $command->transferDate,
                'expected_date' => $command->expectedDate,
                'reason' => $command->reason,
                'reason_detail' => $command->reasonDetail,
                'driver_id' => $command->driverId,
                'vehicle_plate' => $command->vehiclePlate,
                'route_id' => $command->routeId,
                'notes' => $command->notes,
            ]);

            $this->syncLines($model, $command->lines, $unitCosts);
            $this->refreshTotals($model);
        });
    }

    public function updateStatus(Transfer $model, UpdateStatusTransferCommand $command): void
    {
        $attributes = ['status' => $command->status];

        if ($command->status === 'cancelled') {
            $attributes['cancelled_at'] = now();
        }

        $model->update($attributes);
    }

    /**
     * Confirmar es lo que pone la mercancía en la calle. Con bodega de tránsito
     * queda viajando; sin ella, llegó en el mismo acto.
     */
    public function writeShipment(Transfer $model, ?string $sentBy): Transfer
    {
        $model->update([
            'sent_by' => $sentBy,
            'transfer_status' => $model->isTwoStep() ? 'in_transit' : 'received',
            'received_date' => $model->isTwoStep() ? null : $model->transfer_date?->toDateString(),
        ]);

        return $model;
    }

    public function writeReceipt(Transfer $model, WriteTransferReceiptCommand $command): Transfer
    {
        return DB::transaction(function () use ($model, $command): Transfer {
            foreach ($command->lines as $lineId => $quantities) {
                TransferLine::query()
                    ->where('transfer_id', $model->id)
                    ->whereKey($lineId)
                    ->update([
                        'received_quantity' => $quantities['received'],
                        'difference_quantity' => $quantities['difference'],
                    ]);
            }

            $model->update([
                'transfer_status' => $command->transferStatus,
                'status' => $command->status,
                'received_date' => $command->receivedDate,
                'received_by' => $command->receivedBy,
                'notes' => $command->notes ?? $model->notes,
            ]);

            return $model;
        });
    }

    /**
     * El costo con el que la mercancía salió de verdad, y lo que salió.
     *
     * Al escribirlo se rehacen los importes de la línea: el traslado no pone
     * precio, así que su valor **es** ese costo.
     */
    public function writeLineCost(TransferLine $line, WriteTransferLineCostCommand $command): TransferLine
    {
        $unitCost = round($command->unitCost, 6);
        $value = round((float) $line->base_quantity * $unitCost, 2);

        $line->update([
            'unit_cost' => $unitCost,
            'sent_quantity' => round($command->sentQuantity, 4),
            'unit_price' => round($unitCost * $line->baseFactor(), 6),
            'subtotal' => $value,
            'total' => $value,
        ]);

        return $line;
    }

    /**
     * Totales de la cabecera. Suman **solo** las líneas activas.
     *
     * El costo va en unidad base, que es la unidad en la que el kardex valora:
     * `total_cost` es el valor que se mueve de una bodega a otra, y por eso el
     * inventario de la empresa no cambia de valor al trasladar.
     */
    public function refreshTotals(Transfer $model): Transfer
    {
        $lines = TransferLine::query()
            ->where('transfer_id', $model->id)
            ->where('status', 'active')
            ->get();

        $quantity = 0.0;
        $cost = 0.0;

        foreach ($lines as $line) {
            $quantity += (float) $line->quantity;
            $cost += (float) $line->base_quantity * (float) $line->unit_cost;
        }

        $model->update([
            'total_quantity' => round($quantity, 4),
            'total_cost' => round($cost, 2),
        ]);

        return $model;
    }

    /**
     * @return array{ data: Transfer[], total: int }
     */
    public function search(SearchTransferCommand $command): array
    {
        $query = Transfer::query()
            ->with(['originWarehouse', 'destinationWarehouse', 'driver'])
            ->when($command->companyId, fn ($q) => $q->where('company_id', $command->companyId));

        $query = $this->apply($query, $command->filters);

        $total = $query->count();

        $data = $query->orderByDesc('transfer_date')
            ->orderByDesc('code')
            ->limit($command->limit)
            ->offset($command->offset)
            ->get();

        return ['data' => $data->all(), 'total' => $total];
    }

    /**
     * @return array<int, TransferLine>
     */
    public function activeLines(Transfer $model): array
    {
        return TransferLine::query()
            ->with(['item'])
            ->where('transfer_id', $model->id)
            ->where('status', 'active')
            ->orderBy('line_number')
            ->get()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function detailRelations(): array
    {
        return [
            'originWarehouse',
            'destinationWarehouse',
            'transitWarehouse',
            'driver',
            'sender',
            'receiver',
            'lines.item',
            'lines.measurementUnit',
            'lines.lot',
            'lines.serial',
            'lines.originLocation',
            'lines.destinationLocation',
        ];
    }

    /**
     * Alinea `app_transfer_lines` con lo enviado desde la pantalla.
     *
     * Las filas existentes se reconocen por su `id` y conservan su
     * `line_number`; las nuevas toman el siguiente número libre. Las que dejan
     * de venir no se borran, se desactivan (política de no borrado), y por eso
     * los números no se recalculan: el par `(traslado, line_number)` es único
     * y una fila inactiva sigue ocupando el suyo.
     *
     * @param  array<int, TransferLineData>  $lines
     * @param  array<int, float>  $unitCosts  Costo de salida ya resuelto, por
     *                                        unidad base, con la misma clave
     *                                        que la línea.
     */
    private function syncLines(Transfer $transfer, array $lines, array $unitCosts): void
    {
        $existing = TransferLine::query()
            ->where('transfer_id', $transfer->id)
            ->get()
            ->keyBy('id');

        $factors = $this->conversionFactors($transfer->company_id, $lines);
        $nextNumber = (int) $existing->max('line_number');
        $keep = [];

        foreach ($lines as $index => $line) {
            $current = $line->id !== null ? $existing->get($line->id) : null;

            $factor = $factors[$line->itemId.'|'.$line->measurementUnitId] ?? 1.0;
            $baseQuantity = round($line->quantity * $factor, 4);
            $unitCost = round($unitCosts[$index] ?? 0.0, 6);
            /** El traslado no pone precio: el valor de la línea es su costo. */
            $value = round($baseQuantity * $unitCost, 2);

            $attributes = [
                'company_id' => $transfer->company_id,
                'item_id' => $line->itemId,
                'measurement_unit_id' => $line->measurementUnitId,
                'origin_location_id' => $line->originLocationId,
                'destination_location_id' => $line->destinationLocationId,
                'lot_id' => $line->lotId,
                'serial_id' => $line->serialId,
                'quantity' => $line->quantity,
                'base_quantity' => $baseQuantity,
                'unit_cost' => $unitCost,
                'unit_price' => round($unitCost * $factor, 6),
                'subtotal' => $value,
                'total' => $value,
                'status' => $line->status,
                'notes' => $line->notes,
            ];

            if ($current !== null) {
                $current->update($attributes);
                $keep[] = $current->id;

                continue;
            }

            $keep[] = TransferLine::create([
                ...$attributes,
                'transfer_id' => $transfer->id,
                'line_number' => ++$nextNumber,
            ])->id;
        }

        TransferLine::query()
            ->where('transfer_id', $transfer->id)
            ->when($keep !== [], fn ($q) => $q->whereNotIn('id', $keep))
            ->update(['status' => 'inactive']);
    }

    /**
     * Factor de conversión a la unidad base por par artículo/unidad.
     *
     * Se resuelve a través del repositorio de artículos: el módulo de traslados
     * nunca consulta las tablas del módulo de inventario directamente. Un par
     * sin unidad registrada cae en 1, que es lo que valida el Request.
     *
     * @param  array<int, TransferLineData>  $lines
     * @return array<string, float>
     */
    private function conversionFactors(?string $companyId, array $lines): array
    {
        $factors = [];

        foreach (array_unique(array_map(fn (TransferLineData $line): string => $line->itemId, $lines)) as $itemId) {
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
     * Generate the next sequential per-company code (TRA000001, TRA000002, …).
     */
    private function generateNextCode(string $companyId): string
    {
        $last = Transfer::query()
            ->where('company_id', $companyId)
            ->where('code', 'like', Transfer::CODE_PREFIX.'%')
            ->lockForUpdate()
            ->orderByDesc('code')
            ->value('code');

        $next = $last !== null
            ? ((int) substr($last, strlen(Transfer::CODE_PREFIX))) + 1
            : 1;

        return Transfer::CODE_PREFIX.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
