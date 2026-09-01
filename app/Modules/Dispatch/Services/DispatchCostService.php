<?php

declare(strict_types=1);

namespace App\Modules\Dispatch\Services;

use App\Modules\Dispatch\Commands\DispatchLineData;
use App\Modules\Item\Models\Item;
use App\Modules\Item\Repositories\Contracts\ItemRepositoryInterface;

/**
 * A qué costo sale cada línea mientras el despacho es un borrador.
 *
 * Una salida se valora al **promedio vigente** del artículo, que es lo que el
 * kardex usará cuando el despacho se confirme. Aquí se resuelve solo para que
 * la pantalla enseñe el costo de la carga antes de sacarla; el valor definitivo
 * lo escribe `DispatchPostingService` con el que el kardex usó de verdad, que
 * es el único que cuenta.
 *
 * El costo no se captura en la pantalla: no es una decisión del usuario.
 */
class DispatchCostService
{
    public function __construct(
        private readonly ItemRepositoryInterface $items,
    ) {}

    /**
     * @param  array<int, DispatchLineData>  $lines
     * @return array<int, float> Costo unitario por línea, con la misma clave.
     */
    public function resolve(?string $companyId, array $lines): array
    {
        $averages = $this->averageCosts($companyId, $lines);

        $costs = [];

        foreach ($lines as $index => $line) {
            $costs[$index] = round($averages[$line->itemId] ?? 0.0, 6);
        }

        return $costs;
    }

    /**
     * Costo promedio de cada artículo del despacho, resuelto a través del
     * repositorio de artículos: este módulo nunca consulta las tablas del
     * módulo de inventario directamente.
     *
     * @param  array<int, DispatchLineData>  $lines
     * @return array<string, float>
     */
    private function averageCosts(?string $companyId, array $lines): array
    {
        $averages = [];

        $itemIds = array_unique(array_map(
            static fn (DispatchLineData $line): string => $line->itemId,
            $lines,
        ));

        foreach ($itemIds as $itemId) {
            $item = $this->items->findById($itemId, $companyId);

            if ($item instanceof Item) {
                $averages[$itemId] = (float) $item->average_cost;
            }
        }

        return $averages;
    }
}
