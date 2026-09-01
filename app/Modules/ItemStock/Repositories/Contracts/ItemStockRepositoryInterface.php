<?php

declare(strict_types=1);

namespace App\Modules\ItemStock\Repositories\Contracts;

use App\Modules\ItemStock\Commands\ApplyItemStockMovementCommand;
use App\Modules\ItemStock\Commands\SearchItemStockCommand;
use App\Modules\ItemStock\Commands\UpdateStatusItemStockCommand;
use App\Modules\ItemStock\Commands\WriteItemStockBalanceCommand;
use App\Modules\ItemStock\Models\ItemStock;

interface ItemStockRepositoryInterface
{
    public function findById(string $id, ?string $companyId = null): ?ItemStock;

    public function findOrFail(string $id, ?string $companyId = null): ItemStock;

    public function updateStatus(ItemStock $model, UpdateStatusItemStockCommand $command): void;

    /**
     * Bloquea con `lockForUpdate()` el saldo que el movimiento afecta y lo
     * devuelve, creándolo en cero si es la primera vez que ese artículo toca
     * esa ubicación. Debe llamarse dentro de la transacción del documento que
     * origina el movimiento.
     */
    public function lockBalance(ApplyItemStockMovementCommand $command): ItemStock;

    /** Escribe sobre la fila bloqueada el saldo que el servicio ya calculó. */
    public function writeBalance(ItemStock $stock, WriteItemStockBalanceCommand $command): ItemStock;

    /**
     * Saldo consolidado del artículo en la bodega: suma todas sus ubicaciones
     * y todos sus lotes. Es el número que el kardex congela en `balance_*`,
     * porque el movimiento se lee por artículo/bodega y no por ubicación.
     *
     * @return array{quantity: float, value: float}
     */
    public function warehouseBalance(?string $companyId, string $itemId, string $warehouseId): array;

    /**
     * Saldo consolidado del artículo en toda la empresa: suma todas sus
     * bodegas. Es el número del que sale el costo promedio del maestro de
     * artículos, que no distingue bodegas.
     *
     * @return array{quantity: float, value: float}
     */
    public function companyBalance(?string $companyId, string $itemId): array;

    /** @return array{ data: ItemStock[], total: int } */
    public function search(SearchItemStockCommand $command): array;
}
