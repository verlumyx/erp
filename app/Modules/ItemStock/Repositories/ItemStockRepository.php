<?php

declare(strict_types=1);

namespace App\Modules\ItemStock\Repositories;

use App\Modules\ItemStock\Commands\ApplyItemStockMovementCommand;
use App\Modules\ItemStock\Commands\SearchItemStockCommand;
use App\Modules\ItemStock\Commands\UpdateStatusItemStockCommand;
use App\Modules\ItemStock\Commands\WriteItemStockBalanceCommand;
use App\Modules\ItemStock\Models\ItemStock;
use App\Modules\ItemStock\Repositories\Contracts\ItemStockRepositoryInterface;
use Illuminate\Support\Str;

class ItemStockRepository extends ItemStockFilters implements ItemStockRepositoryInterface
{
    /** @var array<int, string> */
    private const RELATIONS = ['item', 'warehouse', 'location', 'lot'];

    public function findById(string $id, ?string $companyId = null): ?ItemStock
    {
        return ItemStock::query()
            ->with(self::RELATIONS)
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->find($id);
    }

    public function findOrFail(string $id, ?string $companyId = null): ItemStock
    {
        return ItemStock::query()
            ->with(self::RELATIONS)
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->findOrFail($id);
    }

    public function updateStatus(ItemStock $model, UpdateStatusItemStockCommand $command): void
    {
        $model->update([
            'status' => $command->status,
        ]);
    }

    /**
     * El `lockForUpdate()` serializa dos documentos que descargan el mismo
     * artículo a la vez: sin él, ambos leerían el mismo saldo de partida y el
     * segundo pisaría al primero.
     */
    public function lockBalance(ApplyItemStockMovementCommand $command): ItemStock
    {
        $existing = ItemStock::query()
            ->where('company_id', $command->companyId)
            ->where('item_id', $command->itemId)
            ->where('warehouse_id', $command->warehouseId)
            ->where('location_id', $command->locationId)
            ->when(
                $command->lotId === null,
                fn ($q) => $q->whereNull('lot_id'),
                fn ($q) => $q->where('lot_id', $command->lotId),
            )
            ->lockForUpdate()
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return ItemStock::create([
            'id' => (string) Str::uuid7(),
            'company_id' => $command->companyId,
            'item_id' => $command->itemId,
            'warehouse_id' => $command->warehouseId,
            'location_id' => $command->locationId,
            'lot_id' => $command->lotId,
            'quantity' => 0,
            'reserved_quantity' => 0,
            'incoming_quantity' => 0,
            'available_quantity' => 0,
            'average_cost' => 0,
            'total_value' => 0,
            'status' => 'active',
        ]);
    }

    public function writeBalance(ItemStock $stock, WriteItemStockBalanceCommand $command): ItemStock
    {
        $stock->update([
            'quantity' => $command->quantity,
            'reserved_quantity' => $command->reservedQuantity,
            'incoming_quantity' => $command->incomingQuantity,
            'available_quantity' => $command->availableQuantity,
            'average_cost' => $command->averageCost,
            'total_value' => $command->totalValue,
            'last_movement_at' => $command->lastMovementAt,
        ]);

        return $stock;
    }

    /**
     * @return array{quantity: float, value: float}
     */
    public function warehouseBalance(?string $companyId, string $itemId, string $warehouseId): array
    {
        $row = ItemStock::query()
            ->selectRaw('COALESCE(SUM(quantity), 0) as quantity, COALESCE(SUM(total_value), 0) as value')
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('item_id', $itemId)
            ->where('warehouse_id', $warehouseId)
            ->first();

        return [
            'quantity' => (float) ($row?->quantity ?? 0),
            'value' => (float) ($row?->value ?? 0),
        ];
    }

    /**
     * @return array{ data: ItemStock[], total: int }
     */
    public function search(SearchItemStockCommand $command): array
    {
        $query = ItemStock::query()
            ->with(self::RELATIONS)
            ->when($command->companyId, fn ($q) => $q->where('company_id', $command->companyId));

        $query = $this->apply($query, $command->filters);

        $total = $query->count();

        $data = $query->orderBy('item_id')
            ->orderBy('warehouse_id')
            ->orderBy('location_id')
            ->limit($command->limit)
            ->offset($command->offset)
            ->get();

        return ['data' => $data->all(), 'total' => $total];
    }
}
