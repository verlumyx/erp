<?php

declare(strict_types=1);

namespace App\Modules\ItemStock\Services;

use App\Modules\ItemStock\Commands\ApplyItemStockMovementCommand;
use App\Modules\ItemStock\Commands\WriteItemStockBalanceCommand;
use App\Modules\ItemStock\Exceptions\InsufficientStockException;
use App\Modules\ItemStock\Exceptions\InvalidStockLocationException;
use App\Modules\ItemStock\Models\ItemStock;
use App\Modules\ItemStock\Repositories\Contracts\ItemStockRepositoryInterface;
use App\Modules\Warehouse\Repositories\Contracts\WarehouseRepositoryInterface;
use App\Modules\WarehouseLocation\Repositories\Contracts\WarehouseLocationRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * El único camino que escribe en `app_item_stocks`.
 *
 * Recibe la afectación con signo, bloquea el saldo, comprueba las dos reglas
 * que la ficha de Existencias impone —la ubicación pertenece a la bodega y la
 * salida no deja negativo si la bodega no lo admite— y persiste el resultado.
 *
 * La transacción es anidable: llamado desde el documento que origina el
 * movimiento se suma a la transacción abierta como savepoint, y llamado suelto
 * abre la suya. En ninguno de los dos casos el saldo se toca fuera de una.
 */
class ItemStockApplyMovementService
{
    public function __construct(
        private readonly ItemStockRepositoryInterface $repository,
        private readonly WarehouseRepositoryInterface $warehouseRepository,
        private readonly WarehouseLocationRepositoryInterface $locationRepository,
    ) {}

    public function execute(ApplyItemStockMovementCommand $command): ItemStock
    {
        return DB::transaction(function () use ($command): ItemStock {
            $warehouse = $this->warehouseRepository->findById($command->warehouseId, $command->companyId);
            $location = $this->locationRepository->findById($command->locationId, $command->companyId);

            if ($warehouse === null || $location === null || $location->warehouse_id !== $warehouse->id) {
                throw new InvalidStockLocationException;
            }

            $stock = $this->repository->lockBalance($command);

            $quantity = (float) $stock->quantity + $command->quantityDelta;

            if ($quantity < 0 && $warehouse->allows_negative_stock === 'no') {
                throw new InsufficientStockException;
            }

            $averageCost = $this->weightedAverageCost(
                currentQuantity: (float) $stock->quantity,
                currentAverage: (float) $stock->average_cost,
                command: $command,
            );

            $reserved = (float) $stock->reserved_quantity + $command->reservedDelta;

            return $this->repository->writeBalance($stock, new WriteItemStockBalanceCommand(
                quantity: $quantity,
                reservedQuantity: $reserved,
                incomingQuantity: (float) $stock->incoming_quantity + $command->incomingDelta,
                availableQuantity: $quantity - $reserved,
                averageCost: $averageCost,
                totalValue: round($quantity * $averageCost, 2),
                lastMovementAt: $command->movementAt ?? now()->toDateTimeString(),
            ));
        });
    }

    /**
     * Promedio ponderado: solo lo mueve una entrada con costo. Una salida sale
     * al promedio vigente, y una entrada que deja el saldo en cero o negativo
     * no tiene sobre qué ponderar, así que conserva el promedio anterior.
     */
    private function weightedAverageCost(
        float $currentQuantity,
        float $currentAverage,
        ApplyItemStockMovementCommand $command,
    ): float {
        if ($command->quantityDelta <= 0 || $command->unitCost === null) {
            return $currentAverage;
        }

        $newQuantity = $currentQuantity + $command->quantityDelta;

        if ($newQuantity <= 0) {
            return $currentAverage;
        }

        /*
         * Un saldo negativo no aporta valor a ponderar: se toma como cero para
         * que el promedio no se distorsione con la deuda que dejó una salida
         * en una bodega que admite negativo.
         */
        $baseQuantity = max($currentQuantity, 0);
        $baseValue = $baseQuantity * $currentAverage;
        $incomingValue = $command->quantityDelta * $command->unitCost;

        return round(($baseValue + $incomingValue) / ($baseQuantity + $command->quantityDelta), 6);
    }
}
