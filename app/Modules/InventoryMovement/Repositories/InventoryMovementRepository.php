<?php

declare(strict_types=1);

namespace App\Modules\InventoryMovement\Repositories;

use App\Modules\InventoryMovement\Commands\RegisterInventoryMovementCommand;
use App\Modules\InventoryMovement\Commands\SearchInventoryMovementCommand;
use App\Modules\InventoryMovement\Commands\WriteInventoryMovementCommand;
use App\Modules\InventoryMovement\Models\InventoryMovement;
use App\Modules\InventoryMovement\Repositories\Contracts\InventoryMovementRepositoryInterface;
use Illuminate\Support\Str;

class InventoryMovementRepository extends InventoryMovementFilters implements InventoryMovementRepositoryInterface
{
    /** @var array<int, string> */
    private const RELATIONS = ['item', 'warehouse', 'location', 'lot', 'serial'];

    public function register(
        RegisterInventoryMovementCommand $command,
        WriteInventoryMovementCommand $write,
    ): InventoryMovement {
        return InventoryMovement::create([
            'id' => (string) Str::uuid7(),
            'company_id' => $command->companyId,
            'code' => $this->generateNextCode($command->companyId),
            'movement_date' => $command->movementDate ?? now()->toDateTimeString(),
            'type' => $command->type,
            'origin_type' => $command->originType,
            'origin_id' => $command->originId,
            'origin_line_id' => $command->originLineId,
            'item_id' => $command->itemId,
            'warehouse_id' => $command->warehouseId,
            'location_id' => $command->locationId,
            'lot_id' => $command->lotId,
            'serial_id' => $command->serialId,
            'quantity' => $command->quantity,
            'unit_cost' => $write->unitCost,
            'total_cost' => $write->totalCost,
            'balance_quantity' => $write->balanceQuantity,
            'balance_cost' => $write->balanceCost,
            'balance_value' => $write->balanceValue,
            'reversal_of_id' => $command->reversalOfId,
            'status' => 'active',
            'notes' => $command->notes,
            'created_by' => $command->createdBy,
        ]);
    }

    public function findById(string $id, ?string $companyId = null): ?InventoryMovement
    {
        return InventoryMovement::query()
            ->with([...self::RELATIONS, 'reversalOf', 'reversal'])
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->find($id);
    }

    public function findOrFail(string $id, ?string $companyId = null): InventoryMovement
    {
        return InventoryMovement::query()
            ->with([...self::RELATIONS, 'reversalOf', 'reversal'])
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->findOrFail($id);
    }

    public function markReversed(InventoryMovement $movement): void
    {
        $movement->update([
            'status' => 'reversed',
        ]);
    }

    /**
     * @return array{ data: InventoryMovement[], total: int }
     */
    public function search(SearchInventoryMovementCommand $command): array
    {
        $query = InventoryMovement::query()
            ->with(self::RELATIONS)
            ->when($command->companyId, fn ($q) => $q->where('company_id', $command->companyId));

        $query = $this->apply($query, $command->filters);

        $total = $query->count();

        /** El kardex se lee del movimiento más reciente hacia atrás. */
        $data = $query->orderByDesc('movement_date')
            ->orderByDesc('code')
            ->limit($command->limit)
            ->offset($command->offset)
            ->get();

        return ['data' => $data->all(), 'total' => $total];
    }

    /**
     * Generate the next sequential per-company code (MOV000001, MOV000002, …).
     */
    private function generateNextCode(string $companyId): string
    {
        $last = InventoryMovement::query()
            ->where('company_id', $companyId)
            ->where('code', 'like', InventoryMovement::CODE_PREFIX.'%')
            ->lockForUpdate()
            ->orderByDesc('code')
            ->value('code');

        $next = $last !== null
            ? ((int) substr($last, strlen(InventoryMovement::CODE_PREFIX))) + 1
            : 1;

        return InventoryMovement::CODE_PREFIX.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
