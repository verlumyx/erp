<?php

declare(strict_types=1);

namespace App\Modules\Item\Services;

use App\Modules\Item\Commands\ApplyItemAverageCostCommand;
use App\Modules\Item\Commands\WriteItemAverageCostCommand;
use App\Modules\Item\Models\Item;
use App\Modules\Item\Repositories\Contracts\ItemRepositoryInterface;
use App\Modules\ItemStock\Repositories\Contracts\ItemStockRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * El único camino que mueve `average_cost` del maestro de artículos.
 *
 * El promedio real vive en la existencia —`app_item_stocks` lo pondera en cada
 * entrada—, y el artículo guarda el consolidado de toda la empresa para que
 * quien no consulta el kardex —una devolución sin factura de origen, un
 * informe— tenga a mano con cuánto vale una unidad.
 *
 * Sin saldo no hay nada que ponderar, así que el promedio anterior se conserva:
 * un inventario en cero no significa que la mercancía haya pasado a valer cero.
 *
 * La transacción es anidable: llamado desde el documento que movió la
 * existencia se suma a la transacción abierta como savepoint.
 */
class ItemApplyAverageCostService
{
    public function __construct(
        private readonly ItemRepositoryInterface $repository,
        private readonly ItemStockRepositoryInterface $stocks,
    ) {}

    public function execute(ApplyItemAverageCostCommand $command): ?Item
    {
        return DB::transaction(function () use ($command): ?Item {
            $item = $this->repository->findById($command->itemId, $command->companyId);

            if ($item === null) {
                return null;
            }

            $balance = $this->stocks->companyBalance($command->companyId, $item->id);

            if ($balance['quantity'] <= 0.0) {
                return $item;
            }

            return $this->repository->writeAverageCost($item, new WriteItemAverageCostCommand(
                averageCost: round($balance['value'] / $balance['quantity'], 6),
            ));
        });
    }
}
