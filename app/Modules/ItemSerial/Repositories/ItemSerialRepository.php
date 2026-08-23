<?php

declare(strict_types=1);

namespace App\Modules\ItemSerial\Repositories;

use App\Modules\ItemSerial\Commands\CreateItemSerialCommand;
use App\Modules\ItemSerial\Commands\SearchItemSerialCommand;
use App\Modules\ItemSerial\Commands\UpdateItemSerialCommand;
use App\Modules\ItemSerial\Commands\UpdateStatusItemSerialCommand;
use App\Modules\ItemSerial\Models\ItemSerial;
use App\Modules\ItemSerial\Repositories\Contracts\ItemSerialRepositoryInterface;
use Illuminate\Support\Facades\DB;

class ItemSerialRepository extends ItemSerialFilters implements ItemSerialRepositoryInterface
{
    public function create(CreateItemSerialCommand $command): void
    {
        DB::transaction(function () use ($command): void {
            ItemSerial::create([
                'id' => $command->id,
                'company_id' => $command->companyId,
                'code' => $this->generateNextCode($command->companyId),
                'item_id' => $command->itemId,
                'serial_number' => $command->serialNumber,
                'lot_id' => $command->lotId,
                'warehouse_id' => $command->warehouseId,
                'status' => $command->status,
                'sold_at' => $command->status === ItemSerial::STATUS_SOLD ? now() : null,
                'created_by' => $command->createdBy,
            ]);
        });
    }

    public function findById(string $id, ?string $companyId = null): ?ItemSerial
    {
        return ItemSerial::query()
            ->with(['item', 'lot', 'warehouse'])
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->find($id);
    }

    public function findOrFail(string $id, ?string $companyId = null): ItemSerial
    {
        return ItemSerial::query()
            ->with(['item', 'lot', 'warehouse'])
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->findOrFail($id);
    }

    public function update(ItemSerial $model, UpdateItemSerialCommand $command): void
    {
        $model->update([
            'serial_number' => $command->serialNumber,
            'lot_id' => $command->lotId,
            'warehouse_id' => $command->warehouseId,
        ]);
    }

    /**
     * `sold_at` no lo captura el usuario: lo sella el paso a `sold` y lo borra
     * la salida de ese estado (una devolución vuelve la unidad al inventario).
     */
    public function updateStatus(ItemSerial $model, UpdateStatusItemSerialCommand $command): void
    {
        $model->update([
            'status' => $command->status,
            'sold_at' => $command->status === ItemSerial::STATUS_SOLD ? ($model->sold_at ?? now()) : null,
        ]);
    }

    public function serialNumberExists(string $companyId, string $itemId, string $serialNumber): bool
    {
        return ItemSerial::query()
            ->where('company_id', $companyId)
            ->where('item_id', $itemId)
            ->where('serial_number', $serialNumber)
            ->exists();
    }

    /**
     * @return array{ data: ItemSerial[], total: int }
     */
    public function search(SearchItemSerialCommand $command): array
    {
        $query = ItemSerial::query()
            ->with(['item', 'lot', 'warehouse'])
            ->when($command->companyId, fn ($q) => $q->where('company_id', $command->companyId));

        $query = $this->apply($query, $command->filters);

        $total = $query->count();

        $data = $query->orderBy('item_id')
            ->orderBy('serial_number')
            ->limit($command->limit)
            ->offset($command->offset)
            ->get();

        return ['data' => $data->all(), 'total' => $total];
    }

    /**
     * Generate the next sequential per-company code (SER000001, SER000002, …).
     */
    private function generateNextCode(string $companyId): string
    {
        $last = ItemSerial::query()
            ->where('company_id', $companyId)
            ->where('code', 'like', ItemSerial::CODE_PREFIX.'%')
            ->lockForUpdate()
            ->orderByDesc('code')
            ->value('code');

        $next = $last !== null
            ? ((int) substr($last, strlen(ItemSerial::CODE_PREFIX))) + 1
            : 1;

        return ItemSerial::CODE_PREFIX.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
