<?php

declare(strict_types=1);

namespace App\Modules\ItemLot\Repositories;

use App\Modules\ItemLot\Commands\CreateItemLotCommand;
use App\Modules\ItemLot\Commands\SearchItemLotCommand;
use App\Modules\ItemLot\Commands\UpdateItemLotCommand;
use App\Modules\ItemLot\Commands\UpdateStatusItemLotCommand;
use App\Modules\ItemLot\Models\ItemLot;
use App\Modules\ItemLot\Repositories\Contracts\ItemLotRepositoryInterface;
use Illuminate\Support\Facades\DB;

class ItemLotRepository extends ItemLotFilters implements ItemLotRepositoryInterface
{
    public function create(CreateItemLotCommand $command): void
    {
        DB::transaction(function () use ($command): void {
            ItemLot::create([
                'id' => $command->id,
                'company_id' => $command->companyId,
                'code' => $this->generateNextCode($command->companyId),
                'item_id' => $command->itemId,
                'lot_number' => $command->lotNumber,
                'manufactured_at' => $command->manufacturedAt,
                'expires_at' => $command->expiresAt,
                'supplier_id' => $command->supplierId,
                'status' => $command->status,
                'created_by' => $command->createdBy,
            ]);
        });
    }

    public function findById(string $id, ?string $companyId = null): ?ItemLot
    {
        return ItemLot::query()
            ->with(['item', 'supplier'])
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->find($id);
    }

    public function findOrFail(string $id, ?string $companyId = null): ItemLot
    {
        return ItemLot::query()
            ->with(['item', 'supplier'])
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->findOrFail($id);
    }

    public function update(ItemLot $model, UpdateItemLotCommand $command): void
    {
        $model->update([
            'lot_number' => $command->lotNumber,
            'manufactured_at' => $command->manufacturedAt,
            'expires_at' => $command->expiresAt,
            'supplier_id' => $command->supplierId,
        ]);
    }

    public function updateStatus(ItemLot $model, UpdateStatusItemLotCommand $command): void
    {
        $model->update([
            'status' => $command->status,
        ]);
    }

    public function lotNumberExists(string $companyId, string $itemId, string $lotNumber): bool
    {
        return ItemLot::query()
            ->where('company_id', $companyId)
            ->where('item_id', $itemId)
            ->where('lot_number', $lotNumber)
            ->exists();
    }

    /**
     * El orden es FEFO (First Expired, First Out): el lote que vence primero
     * encabeza la lista, y los que no vencen quedan al final. Es el mismo orden
     * en el que la salida de inventario debe consumirlos.
     *
     * @return array{ data: ItemLot[], total: int }
     */
    public function search(SearchItemLotCommand $command): array
    {
        $query = ItemLot::query()
            ->with(['item', 'supplier'])
            ->when($command->companyId, fn ($q) => $q->where('company_id', $command->companyId));

        $query = $this->apply($query, $command->filters);

        $total = $query->count();

        $data = $query->orderByRaw('expires_at IS NULL')
            ->orderBy('expires_at')
            ->orderBy('lot_number')
            ->limit($command->limit)
            ->offset($command->offset)
            ->get();

        return ['data' => $data->all(), 'total' => $total];
    }

    /**
     * Generate the next sequential per-company code (LOT000001, LOT000002, …).
     */
    private function generateNextCode(string $companyId): string
    {
        $last = ItemLot::query()
            ->where('company_id', $companyId)
            ->where('code', 'like', ItemLot::CODE_PREFIX.'%')
            ->lockForUpdate()
            ->orderByDesc('code')
            ->value('code');

        $next = $last !== null
            ? ((int) substr($last, strlen(ItemLot::CODE_PREFIX))) + 1
            : 1;

        return ItemLot::CODE_PREFIX.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
