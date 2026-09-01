<?php

declare(strict_types=1);

namespace App\Modules\Transfer\Services;

use App\Modules\Item\Models\Item;
use App\Modules\Item\Repositories\Contracts\ItemRepositoryInterface;
use App\Modules\ItemStock\Repositories\Contracts\ItemStockRepositoryInterface;
use App\Modules\Transfer\Commands\TransferLineData;

/**
 * A qué costo viaja cada línea mientras el traslado es un borrador.
 *
 * La mercancía sale valorada al **promedio de la bodega de origen**, que es lo
 * que el kardex usará cuando el traslado se confirme: una salida no se valora
 * al promedio de la empresa, sino al del saldo del que sale. Aquí se resuelve
 * solo para que la pantalla enseñe el valor que se va a mover antes de moverlo;
 * el definitivo lo escribe `TransferPostingService` con el que el kardex usó de
 * verdad, y ese es el que la bodega de destino recibe.
 *
 * El costo no se captura en la pantalla: no es una decisión del usuario, y
 * mucho menos en un traslado, donde el valor del inventario no puede cambiar.
 */
class TransferCostService
{
    public function __construct(
        private readonly ItemRepositoryInterface $items,
        private readonly ItemStockRepositoryInterface $stocks,
    ) {}

    /**
     * @param  array<int, TransferLineData>  $lines
     * @return array<int, float> Costo por unidad base, con la misma clave que la línea.
     */
    public function resolve(?string $companyId, string $originWarehouseId, array $lines): array
    {
        $averages = $this->averageCosts($companyId, $originWarehouseId, $lines);

        $costs = [];

        foreach ($lines as $index => $line) {
            $costs[$index] = round($averages[$line->itemId] ?? 0.0, 6);
        }

        return $costs;
    }

    /**
     * Costo promedio de cada artículo en la bodega de la que sale.
     *
     * Se resuelve a través de los repositorios de los módulos de inventario:
     * este módulo nunca consulta sus tablas directamente. Un artículo sin saldo
     * en esa bodega cae en el promedio del maestro, que es el consolidado de la
     * empresa: es lo mejor que se puede enseñar antes de mover nada.
     *
     * @param  array<int, TransferLineData>  $lines
     * @return array<string, float>
     */
    private function averageCosts(?string $companyId, string $originWarehouseId, array $lines): array
    {
        $averages = [];

        $itemIds = array_unique(array_map(
            static fn (TransferLineData $line): string => $line->itemId,
            $lines,
        ));

        foreach ($itemIds as $itemId) {
            $balance = $this->stocks->warehouseBalance($companyId, $itemId, $originWarehouseId);

            if ($balance['quantity'] > 0.0) {
                $averages[$itemId] = $balance['value'] / $balance['quantity'];

                continue;
            }

            $item = $this->items->findById($itemId, $companyId);

            if ($item instanceof Item) {
                $averages[$itemId] = (float) $item->average_cost;
            }
        }

        return $averages;
    }
}
