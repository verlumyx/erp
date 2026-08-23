<?php

declare(strict_types=1);

namespace App\Modules\InventoryMovement\Services;

use App\Modules\InventoryMovement\Commands\RegisterInventoryMovementCommand;
use App\Modules\InventoryMovement\Commands\WriteInventoryMovementCommand;
use App\Modules\InventoryMovement\Exceptions\InvalidMovementQuantityException;
use App\Modules\InventoryMovement\Exceptions\InvalidMovementTypeException;
use App\Modules\InventoryMovement\Exceptions\NonInventoriedItemException;
use App\Modules\InventoryMovement\Models\InventoryMovement;
use App\Modules\InventoryMovement\Repositories\Contracts\InventoryMovementRepositoryInterface;
use App\Modules\Item\Repositories\Contracts\ItemRepositoryInterface;
use App\Modules\ItemStock\Commands\ApplyItemStockMovementCommand;
use App\Modules\ItemStock\Models\ItemStock;
use App\Modules\ItemStock\Repositories\Contracts\ItemStockRepositoryInterface;
use App\Modules\ItemStock\Services\ItemStockApplyMovementService;
use Illuminate\Support\Facades\DB;

/**
 * El único camino que escribe en `app_inventory_movements`.
 *
 * Todo cambio de existencia pasa por aquí: el servicio mueve el saldo y anota
 * el asiento **en la misma transacción**, de modo que no puede quedar saldo
 * sin kardex ni kardex sin saldo. Si la bodega rechaza la salida por
 * insuficiencia, la excepción tumba las dos escrituras a la vez.
 *
 * La transacción es anidable: llamado desde el documento que origina el
 * movimiento se suma a la abierta como savepoint.
 */
class InventoryMovementRegisterService
{
    /**
     * Tipos de artículo que no llegan al kardex: no llevan existencia, así que
     * tampoco tienen saldo que mover.
     *
     * @var array<int, string>
     */
    private const NON_STOCKED_ITEM_TYPES = ['service', 'non_inventoried'];

    public function __construct(
        private readonly InventoryMovementRepositoryInterface $repository,
        private readonly ItemStockApplyMovementService $applyMovementService,
        private readonly ItemStockRepositoryInterface $stockRepository,
        private readonly ItemRepositoryInterface $itemRepository,
    ) {}

    public function execute(RegisterInventoryMovementCommand $command): InventoryMovement
    {
        $this->guard($command);

        return DB::transaction(function () use ($command): InventoryMovement {
            $inbound = in_array($command->type, InventoryMovement::INBOUND_TYPES, true);

            $stock = $this->applyMovementService->execute(new ApplyItemStockMovementCommand(
                companyId: $command->companyId,
                itemId: $command->itemId,
                warehouseId: $command->warehouseId,
                locationId: $command->locationId,
                quantityDelta: $inbound ? $command->quantity : -$command->quantity,
                lotId: $command->lotId,
                unitCost: $inbound ? $command->unitCost : null,
                movementAt: $command->movementDate,
            ));

            return $this->repository->register(
                $command,
                $this->write($command, $stock, $inbound),
            );
        });
    }

    /**
     * Arma la parte calculada del asiento a partir del saldo recién escrito.
     *
     * Una salida sin costo explícito se valora al promedio vigente: la salida
     * no mueve el promedio, así que el que devuelve la existencia es el mismo
     * que había antes del movimiento.
     */
    private function write(
        RegisterInventoryMovementCommand $command,
        ItemStock $stock,
        bool $inbound,
    ): WriteInventoryMovementCommand {
        $unitCost = $command->unitCost ?? ($inbound ? 0.0 : (float) $stock->average_cost);

        $balance = $this->stockRepository->warehouseBalance(
            $command->companyId,
            $command->itemId,
            $command->warehouseId,
        );

        return new WriteInventoryMovementCommand(
            unitCost: round($unitCost, 6),
            totalCost: round($command->quantity * $unitCost, 2),
            balanceQuantity: $balance['quantity'],
            balanceCost: $this->balanceCost($balance, $stock),
            balanceValue: round($balance['value'], 2),
        );
    }

    /**
     * Costo promedio de la bodega después del movimiento. Con saldo en cero no
     * hay nada que ponderar, así que se conserva el promedio de la existencia
     * que se acaba de tocar.
     *
     * @param  array{quantity: float, value: float}  $balance
     */
    private function balanceCost(array $balance, ItemStock $stock): float
    {
        if ($balance['quantity'] == 0.0) {
            return round((float) $stock->average_cost, 6);
        }

        return round($balance['value'] / $balance['quantity'], 6);
    }

    private function guard(RegisterInventoryMovementCommand $command): void
    {
        if (! in_array($command->type, InventoryMovement::TYPES, true)) {
            throw new InvalidMovementTypeException;
        }

        if ($command->quantity <= 0) {
            throw new InvalidMovementQuantityException;
        }

        $item = $this->itemRepository->findById($command->itemId, $command->companyId);

        if ($item !== null && in_array($item->type, self::NON_STOCKED_ITEM_TYPES, true)) {
            throw new NonInventoriedItemException;
        }
    }
}
