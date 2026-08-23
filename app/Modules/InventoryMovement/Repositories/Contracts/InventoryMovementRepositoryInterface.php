<?php

declare(strict_types=1);

namespace App\Modules\InventoryMovement\Repositories\Contracts;

use App\Modules\InventoryMovement\Commands\RegisterInventoryMovementCommand;
use App\Modules\InventoryMovement\Commands\SearchInventoryMovementCommand;
use App\Modules\InventoryMovement\Commands\WriteInventoryMovementCommand;
use App\Modules\InventoryMovement\Models\InventoryMovement;

interface InventoryMovementRepositoryInterface
{
    /**
     * Escribe el asiento con el saldo que el servicio ya calculó y genera su
     * código (`MOV000001`). Debe llamarse dentro de la transacción que además
     * actualiza `app_item_stocks`.
     */
    public function register(
        RegisterInventoryMovementCommand $command,
        WriteInventoryMovementCommand $write,
    ): InventoryMovement;

    public function findById(string $id, ?string $companyId = null): ?InventoryMovement;

    public function findOrFail(string $id, ?string $companyId = null): InventoryMovement;

    /**
     * Marca el original como revertido. Es el único cambio que admite una fila
     * del kardex: su contenido contable nunca se toca.
     */
    public function markReversed(InventoryMovement $movement): void;

    /** @return array{ data: InventoryMovement[], total: int } */
    public function search(SearchInventoryMovementCommand $command): array;
}
