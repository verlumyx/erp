<?php

declare(strict_types=1);

namespace App\Modules\InventoryMovement\Services;

use App\Modules\InventoryMovement\Commands\RegisterInventoryMovementCommand;
use App\Modules\InventoryMovement\Commands\ReverseInventoryMovementCommand;
use App\Modules\InventoryMovement\Exceptions\InventoryMovementNotFoundException;
use App\Modules\InventoryMovement\Exceptions\MovementAlreadyReversedException;
use App\Modules\InventoryMovement\Exceptions\MovementNotReversibleException;
use App\Modules\InventoryMovement\Models\InventoryMovement;
use App\Modules\InventoryMovement\Repositories\Contracts\InventoryMovementRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * Anula un movimiento con su contrapartida.
 *
 * El original no se borra ni se edita: se emite un movimiento del tipo opuesto
 * con `reversal_of_id` apuntándole, y el original queda marcado `reversed`. El
 * saldo vuelve solo, porque la contrapartida pasa por el mismo registrador.
 *
 * Lo llama el documento que se anula, no una pantalla del kardex.
 */
class InventoryMovementReverseService
{
    public function __construct(
        private readonly InventoryMovementRepositoryInterface $repository,
        private readonly InventoryMovementRegisterService $registerService,
    ) {}

    public function execute(ReverseInventoryMovementCommand $command): InventoryMovement
    {
        return DB::transaction(function () use ($command): InventoryMovement {
            $original = $this->repository->findById($command->movementId);

            if ($original === null) {
                throw new InventoryMovementNotFoundException;
            }

            if ($original->reversal_of_id !== null) {
                throw new MovementNotReversibleException;
            }

            if ($original->status === 'reversed') {
                throw new MovementAlreadyReversedException;
            }

            $reversal = $this->registerService->execute(new RegisterInventoryMovementCommand(
                companyId: (string) $original->company_id,
                itemId: $original->item_id,
                warehouseId: $original->warehouse_id,
                locationId: (string) $original->location_id,
                type: InventoryMovement::OPPOSITE_TYPES[$original->type],
                originType: $original->origin_type,
                originId: $original->origin_id,
                quantity: (float) $original->quantity,
                /** La contrapartida sale al mismo costo que entró el original. */
                unitCost: (float) $original->unit_cost,
                movementDate: $command->movementDate,
                originLineId: $original->origin_line_id,
                lotId: $original->lot_id,
                serialId: $original->serial_id,
                reversalOfId: $original->id,
                notes: $command->notes,
                createdBy: $command->createdBy,
            ));

            $this->repository->markReversed($original);

            return $reversal;
        });
    }
}
