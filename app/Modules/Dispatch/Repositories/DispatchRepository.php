<?php

declare(strict_types=1);

namespace App\Modules\Dispatch\Repositories;

use App\Modules\Dispatch\Commands\CreateDispatchCommand;
use App\Modules\Dispatch\Commands\DispatchLineData;
use App\Modules\Dispatch\Commands\SearchDispatchCommand;
use App\Modules\Dispatch\Commands\UpdateDispatchCommand;
use App\Modules\Dispatch\Commands\UpdateStatusDispatchCommand;
use App\Modules\Dispatch\Commands\WriteDispatchDeliveryCommand;
use App\Modules\Dispatch\Commands\WriteDispatchLineCostCommand;
use App\Modules\Dispatch\Models\Dispatch;
use App\Modules\Dispatch\Models\DispatchLine;
use App\Modules\Dispatch\Models\DispatchLineLot;
use App\Modules\Dispatch\Models\DispatchLineSerial;
use App\Modules\Dispatch\Repositories\Contracts\DispatchRepositoryInterface;
use App\Modules\Item\Models\Item;
use App\Modules\Item\Models\ItemUnit;
use App\Modules\Item\Repositories\Contracts\ItemRepositoryInterface;
use Illuminate\Support\Facades\DB;

class DispatchRepository extends DispatchFilters implements DispatchRepositoryInterface
{
    public function __construct(
        private readonly ItemRepositoryInterface $items,
    ) {}

    /**
     * @param  array<int, float>  $unitCosts
     * @param  array<int, DispatchLineData>  $lines
     */
    public function create(CreateDispatchCommand $command, array $unitCosts, array $lines): void
    {
        DB::transaction(function () use ($command, $unitCosts, $lines): void {
            $dispatch = Dispatch::create([
                'id' => $command->id,
                'company_id' => $command->companyId,
                'code' => $this->generateNextCode($command->companyId),
                'recipient_type' => $command->recipientType,
                'recipient_id' => $command->recipientId,
                'sourceable_type' => $command->sourceableType,
                'sourceable_id' => $command->sourceableId,
                'client_address_id' => $command->clientAddressId,
                'warehouse_id' => $command->warehouseId,
                'route_id' => $command->routeId,
                'route_stop_id' => $command->routeStopId,
                'dispatch_date' => $command->dispatchDate,
                'driver_id' => $command->driverId,
                'vehicle_plate' => $command->vehiclePlate,
                'carrier' => $command->carrier,
                'tracking_number' => $command->trackingNumber,
                'delivery_status' => 'pending',
                'notes' => $command->notes,
                'status' => 'draft',
                'created_by' => $command->createdBy,
            ]);

            $this->syncLines($dispatch, $lines, $unitCosts);
            $this->refreshTotals($dispatch);
        });
    }

    public function findById(string $id, ?string $companyId = null): ?Dispatch
    {
        return Dispatch::query()
            ->with($this->detailRelations())
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->find($id);
    }

    public function findOrFail(string $id, ?string $companyId = null): Dispatch
    {
        return Dispatch::query()
            ->with($this->detailRelations())
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->findOrFail($id);
    }

    /**
     * @param  array<int, float>  $unitCosts
     * @param  array<int, DispatchLineData>  $lines
     */
    public function update(Dispatch $model, UpdateDispatchCommand $command, array $unitCosts, array $lines): void
    {
        DB::transaction(function () use ($model, $command, $unitCosts, $lines): void {
            /** El resultado del viaje y la marca de anulación no se editan aquí. */
            $model->update([
                'recipient_type' => $command->recipientType,
                'recipient_id' => $command->recipientId,
                'sourceable_type' => $command->sourceableType,
                'sourceable_id' => $command->sourceableId,
                'client_address_id' => $command->clientAddressId,
                'warehouse_id' => $command->warehouseId,
                'route_id' => $command->routeId,
                'route_stop_id' => $command->routeStopId,
                'dispatch_date' => $command->dispatchDate,
                'driver_id' => $command->driverId,
                'vehicle_plate' => $command->vehiclePlate,
                'carrier' => $command->carrier,
                'tracking_number' => $command->trackingNumber,
                'notes' => $command->notes,
            ]);

            $this->syncLines($model, $lines, $unitCosts);
            $this->refreshTotals($model);
        });
    }

    public function updateStatus(Dispatch $model, UpdateStatusDispatchCommand $command): void
    {
        $attributes = ['status' => $command->status];

        /** Confirmar es lo que pone la mercancía en la calle. */
        if ($command->status === 'confirmed' && $model->delivery_status === 'pending') {
            $attributes['delivery_status'] = 'in_transit';
        }

        if ($command->status === 'cancelled') {
            $attributes['cancelled_at'] = now();
        }

        $model->update($attributes);
    }

    public function writeDelivery(Dispatch $model, WriteDispatchDeliveryCommand $command): Dispatch
    {
        return DB::transaction(function () use ($model, $command): Dispatch {
            foreach ($command->lines as $lineId => $quantities) {
                DispatchLine::query()
                    ->where('dispatch_id', $model->id)
                    ->whereKey($lineId)
                    ->update([
                        'delivered_quantity' => $quantities['delivered'],
                        'returned_quantity' => $quantities['returned'],
                    ]);
            }

            $model->update([
                'delivery_status' => $command->deliveryStatus,
                'delivery_date' => $command->deliveryDate,
                'received_by_name' => $command->receivedByName,
                'received_by_document' => $command->receivedByDocument,
                'signature_path' => $command->signaturePath,
                'evidence_path' => $command->evidencePath,
                'latitude' => $command->latitude,
                'longitude' => $command->longitude,
                'rejection_reason' => $command->rejectionReason,
            ]);

            return $model;
        });
    }

    public function writeLineCost(DispatchLine $line, WriteDispatchLineCostCommand $command): DispatchLine
    {
        $line->update(['unit_cost' => round($command->unitCost, 6)]);

        return $line;
    }

    /**
     * La parada en la que se entrega. Es lo único que la planificación de la
     * ruta escribe en el despacho: ni la fecha ni el estado de la entrega se
     * tocan desde ahí.
     */
    public function assignRouteStop(Dispatch $model, ?string $routeStopId): Dispatch
    {
        $model->update(['route_stop_id' => $routeStopId]);

        return $model;
    }

    /**
     * Totales de la cabecera. Suman **solo** las líneas activas.
     *
     * El peso y el volumen salen del artículo, no de la línea: son propiedades
     * de la mercancía y se calculan sobre la cantidad en unidad base, que es la
     * unidad en la que el artículo los declara.
     */
    public function refreshTotals(Dispatch $model): Dispatch
    {
        $lines = DispatchLine::query()
            ->where('dispatch_id', $model->id)
            ->where('status', 'active')
            ->get();

        $measures = $this->itemMeasures(
            $model->company_id,
            $lines->pluck('item_id')->unique()->all(),
        );

        $quantity = 0.0;
        $weight = 0.0;
        $volume = 0.0;
        $cost = 0.0;

        foreach ($lines as $line) {
            $base = (float) $line->base_quantity;

            $quantity += (float) $line->quantity;
            $weight += $base * ($measures[$line->item_id]['weight'] ?? 0.0);
            $volume += $base * ($measures[$line->item_id]['volume'] ?? 0.0);
            $cost += $base * (float) $line->unit_cost;
        }

        $model->update([
            'total_quantity' => round($quantity, 4),
            'total_weight' => round($weight, 4),
            'total_volume' => round($volume, 4),
            'total_cost' => round($cost, 2),
        ]);

        return $model;
    }

    /**
     * @return array{ data: Dispatch[], total: int }
     */
    public function search(SearchDispatchCommand $command): array
    {
        $query = Dispatch::query()
            ->with(['recipient', 'warehouse', 'driver'])
            ->when($command->companyId, fn ($q) => $q->where('company_id', $command->companyId));

        $query = $this->apply($query, $command->filters);

        $total = $query->count();

        $data = $query->orderByDesc('dispatch_date')
            ->orderByDesc('code')
            ->limit($command->limit)
            ->offset($command->offset)
            ->get();

        return ['data' => $data->all(), 'total' => $total];
    }

    /**
     * @return array<int, DispatchLine>
     */
    public function activeLines(Dispatch $model): array
    {
        return DispatchLine::query()
            ->with(['item', 'lots.lot', 'serials.serial', 'serials.dispatchLineLot'])
            ->where('dispatch_id', $model->id)
            ->where('status', 'active')
            ->orderBy('line_number')
            ->get()
            ->all();
    }

    /**
     * Cantidad ya despachada de cada línea origen.
     *
     * Suma solo las líneas activas de despachos que siguen vivos: un despacho
     * anulado devuelve su cupo, y el que se está guardando no compite consigo
     * mismo.
     *
     * @param  array<int, string>  $sourceLineIds
     * @return array<string, float>
     */
    public function dispatchedQuantities(array $sourceLineIds, ?string $exceptDispatchId = null): array
    {
        if ($sourceLineIds === []) {
            return [];
        }

        return DispatchLine::query()
            ->whereIn('sourceable_id', $sourceLineIds)
            ->where('app_dispatch_lines.status', 'active')
            ->when($exceptDispatchId, fn ($q) => $q->where('dispatch_id', '!=', $exceptDispatchId))
            ->whereHas('dispatch', fn ($q) => $q->where('status', '!=', 'cancelled'))
            ->groupBy('sourceable_id')
            ->selectRaw('sourceable_id, SUM(quantity) as dispatched')
            ->pluck('dispatched', 'sourceable_id')
            ->map(fn ($dispatched): float => (float) $dispatched)
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function detailRelations(): array
    {
        return [
            'recipient',
            'clientAddress',
            'warehouse',
            'driver',
            'deliveryRoute',
            'sourceable',
            'lines.item',
            'lines.measurementUnit',
            'lines.lots.lot',
            'lines.serials.serial',
            'lines.serials.dispatchLineLot',
            /** La cantidad que pidió el pedido se lee de la línea origen. */
            'lines.sourceable',
            'lines.location',
        ];
    }

    /**
     * Alinea `app_dispatch_lines` con lo enviado desde la pantalla.
     *
     * Las filas existentes se reconocen por su `id` y conservan su
     * `line_number`; las nuevas toman el siguiente número libre. Las que dejan
     * de venir no se borran, se desactivan (política de no borrado), y por eso
     * los números no se recalculan: el par `(despacho, line_number)` es único
     * y una fila inactiva sigue ocupando el suyo.
     *
     * @param  array<int, DispatchLineData>  $lines
     * @param  array<int, float>  $unitCosts  Costo de salida ya resuelto, con la
     *                                        misma clave que la línea.
     */
    private function syncLines(Dispatch $dispatch, array $lines, array $unitCosts): void
    {
        $existing = DispatchLine::query()
            ->where('dispatch_id', $dispatch->id)
            ->get()
            ->keyBy('id');

        $factors = $this->conversionFactors($dispatch->company_id, $lines);
        $nextNumber = (int) $existing->max('line_number');
        $keep = [];

        foreach ($lines as $index => $line) {
            $current = $line->id !== null ? $existing->get($line->id) : null;

            $factor = $factors[$line->itemId.'|'.$line->measurementUnitId] ?? 1.0;

            $attributes = [
                'company_id' => $dispatch->company_id,
                'item_id' => $line->itemId,
                'measurement_unit_id' => $line->measurementUnitId,
                'sourceable_type' => $line->sourceableType,
                'sourceable_id' => $line->sourceableId,
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
                'status' => $line->status,
                'notes' => $line->notes,
            ];

            if ($current !== null) {
                $current->update($attributes);
                $keep[] = $current->id;
                $this->syncLineTraceability($current, $line, $factor);

                continue;
            }

            $persisted = DispatchLine::create([
                ...$attributes,
                'dispatch_id' => $dispatch->id,
                'line_number' => ++$nextNumber,
            ]);

            $keep[] = $persisted->id;
            $this->syncLineTraceability($persisted, $line, $factor);
        }

        DispatchLine::query()
            ->where('dispatch_id', $dispatch->id)
            ->when($keep !== [], fn ($q) => $q->whereNotIn('id', $keep))
            ->update(['status' => 'inactive']);
    }

    /**
     * Alinea los lotes y las series de una línea con lo enviado.
     *
     * Mismo criterio que las líneas: se reconocen por `id`, conservan su
     * `line_number` y las que dejan de venir se desactivan.
     */
    private function syncLineTraceability(DispatchLine $line, DispatchLineData $data, float $factor): void
    {
        $lotIds = $this->syncLineLots($line, $data, $factor);

        $this->syncLineSerials($line, $data, $lotIds);
    }

    /**
     * @return array<string, string> Id del lote del maestro → id de la fila.
     */
    private function syncLineLots(DispatchLine $line, DispatchLineData $data, float $factor): array
    {
        $existing = DispatchLineLot::query()
            ->where('dispatch_line_id', $line->id)
            ->get()
            ->keyBy('id');

        $nextNumber = (int) $existing->max('line_number');
        $keep = [];
        $byLot = [];

        foreach ($data->lots as $lot) {
            $current = $lot->id !== null ? $existing->get($lot->id) : null;

            $attributes = [
                'company_id' => $line->company_id,
                'lot_id' => $lot->lotId,
                'quantity' => $lot->quantity,
                'base_quantity' => round($lot->quantity * $factor, 4),
                'status' => $lot->status,
                'notes' => $lot->notes,
            ];

            if ($current !== null) {
                $current->update($attributes);
                $keep[] = $current->id;
                $byLot[$lot->lotId] = $current->id;

                continue;
            }

            $persisted = DispatchLineLot::create([
                ...$attributes,
                'dispatch_line_id' => $line->id,
                'line_number' => ++$nextNumber,
            ]);

            $keep[] = $persisted->id;
            $byLot[$lot->lotId] = $persisted->id;
        }

        DispatchLineLot::query()
            ->where('dispatch_line_id', $line->id)
            ->when($keep !== [], fn ($q) => $q->whereNotIn('id', $keep))
            ->update(['status' => 'inactive']);

        return $byLot;
    }

    /**
     * @param  array<string, string>  $lotIds  Id del lote del maestro → id de la fila.
     */
    private function syncLineSerials(DispatchLine $line, DispatchLineData $data, array $lotIds): void
    {
        $existing = DispatchLineSerial::query()
            ->where('dispatch_line_id', $line->id)
            ->get()
            ->keyBy('id');

        $nextNumber = (int) $existing->max('line_number');
        $keep = [];

        foreach ($data->serials as $serial) {
            $current = $serial->id !== null ? $existing->get($serial->id) : null;

            $attributes = [
                'company_id' => $line->company_id,
                'dispatch_line_lot_id' => $serial->lotId !== null
                    ? ($lotIds[$serial->lotId] ?? null)
                    : null,
                'serial_id' => $serial->serialId,
                'status' => $serial->status,
            ];

            if ($current !== null) {
                $current->update($attributes);
                $keep[] = $current->id;

                continue;
            }

            $keep[] = DispatchLineSerial::create([
                ...$attributes,
                'dispatch_line_id' => $line->id,
                'line_number' => ++$nextNumber,
            ])->id;
        }

        DispatchLineSerial::query()
            ->where('dispatch_line_id', $line->id)
            ->when($keep !== [], fn ($q) => $q->whereNotIn('id', $keep))
            ->update(['status' => 'inactive']);
    }

    /**
     * Factor de conversión a la unidad base por par artículo/unidad.
     *
     * Se resuelve a través del repositorio de artículos: el módulo de despachos
     * nunca consulta las tablas del módulo de inventario directamente. Un par
     * sin unidad registrada cae en 1, que es lo que valida el Request.
     *
     * @param  array<int, DispatchLineData>  $lines
     * @return array<string, float>
     */
    private function conversionFactors(?string $companyId, array $lines): array
    {
        $factors = [];

        foreach (array_unique(array_map(fn (DispatchLineData $line): string => $line->itemId, $lines)) as $itemId) {
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
     * Peso y volumen unitarios de cada artículo, en su unidad base.
     *
     * @param  array<int, string>  $itemIds
     * @return array<string, array{weight: float, volume: float}>
     */
    private function itemMeasures(?string $companyId, array $itemIds): array
    {
        $measures = [];

        foreach ($itemIds as $itemId) {
            $item = $this->items->findById($itemId, $companyId);

            if ($item instanceof Item) {
                $measures[$itemId] = [
                    'weight' => (float) $item->weight,
                    'volume' => (float) $item->volume,
                ];
            }
        }

        return $measures;
    }

    /**
     * Generate the next sequential per-company code (DES000001, DES000002, …).
     */
    private function generateNextCode(string $companyId): string
    {
        $last = Dispatch::query()
            ->where('company_id', $companyId)
            ->where('code', 'like', Dispatch::CODE_PREFIX.'%')
            ->lockForUpdate()
            ->orderByDesc('code')
            ->value('code');

        $next = $last !== null
            ? ((int) substr($last, strlen(Dispatch::CODE_PREFIX))) + 1
            : 1;

        return Dispatch::CODE_PREFIX.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
