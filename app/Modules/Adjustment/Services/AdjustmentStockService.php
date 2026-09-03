<?php

declare(strict_types=1);

namespace App\Modules\Adjustment\Services;

use App\Modules\Adjustment\Commands\AdjustmentLineData;
use App\Modules\Adjustment\Commands\AdjustmentLineLotData;
use App\Modules\Item\Models\Item;
use App\Modules\Item\Models\ItemUnit;
use App\Modules\Item\Repositories\Contracts\ItemRepositoryInterface;
use App\Modules\ItemStock\Commands\SearchItemStockCommand;
use App\Modules\ItemStock\Models\ItemStock;
use App\Modules\ItemStock\Repositories\Contracts\ItemStockRepositoryInterface;

/**
 * Contra qué se compara lo contado.
 *
 * Resuelve, para cada línea, la existencia que el sistema cree tener y el
 * costo promedio con el que hoy la valora. Ninguno de los dos se captura: si
 * el usuario pudiera declarar la existencia del sistema, el ajuste dejaría de
 * probar nada.
 *
 * La existencia se lee por la misma clave con la que el kardex la guarda
 * —artículo, bodega, ubicación y lote—; una línea que no fija ubicación
 * compara contra el saldo de toda la bodega, que es lo que se cuenta cuando no
 * se cuenta un estante concreto.
 *
 * Cada lote que la línea cuenta se resuelve aparte, con su propia clave: es lo
 * que permite que el kardex escriba un movimiento por lote, cada uno con su
 * diferencia y su costo. Cuando la línea trae lotes, lo que dice el sistema
 * para la línea es la suma de los suyos: la línea cuenta esos lotes, no todo lo
 * que haya en la ubicación.
 *
 * Todo pasa por los repositorios de sus módulos: el módulo de ajustes nunca
 * consulta las tablas del inventario directamente.
 */
class AdjustmentStockService
{
    public function __construct(
        private readonly ItemStockRepositoryInterface $stocks,
        private readonly ItemRepositoryInterface $items,
    ) {}

    /**
     * @param  array<int, AdjustmentLineData>  $lines
     * @return array<int, array{factor: float, system: float, base_system: float, average: float, lots: array<string, array{system: float, base_system: float, average: float}>}>
     */
    public function resolve(?string $companyId, string $warehouseId, array $lines): array
    {
        $resolved = [];

        foreach ($lines as $index => $line) {
            $lots = $this->resolveLots($companyId, $warehouseId, $line);

            $resolved[$index] = [
                ...$this->lineBalance($companyId, $warehouseId, $line, $lots),
                'lots' => $lots,
            ];
        }

        return $resolved;
    }

    /**
     * Lo que el sistema dice de cada lote que la línea cuenta, indexado por el
     * lote del maestro: dentro de una línea un lote no se repite.
     *
     * @return array<string, array{system: float, base_system: float, average: float}>
     */
    private function resolveLots(?string $companyId, string $warehouseId, AdjustmentLineData $line): array
    {
        $lots = $line->activeLots();

        if ($lots === []) {
            return [];
        }

        $resolved = $this->resolveForKeys($companyId, $warehouseId, array_map(
            static fn (AdjustmentLineLotData $lot): array => [
                'item_id' => $line->itemId,
                'measurement_unit_id' => $line->measurementUnitId,
                'location_id' => $line->locationId,
                'lot_id' => $lot->lotId,
            ],
            $lots,
        ));

        $byLot = [];

        foreach ($lots as $index => $lot) {
            $byLot[$lot->lotId] = [
                'system' => $resolved[$index]['system'],
                'base_system' => $resolved[$index]['base_system'],
                'average' => $resolved[$index]['average'],
            ];
        }

        return $byLot;
    }

    /**
     * Contra qué se compara la línea entera. Sin lotes es el saldo de su clave;
     * con lotes es la suma de los que cuenta, y el costo el promedio ponderado
     * de esos mismos lotes: la línea no está contando nada más.
     *
     * @param  array<string, array{system: float, base_system: float, average: float}>  $lots
     * @return array{factor: float, system: float, base_system: float, average: float}
     */
    private function lineBalance(
        ?string $companyId,
        string $warehouseId,
        AdjustmentLineData $line,
        array $lots,
    ): array {
        $resolved = $this->resolveForKeys($companyId, $warehouseId, [[
            'item_id' => $line->itemId,
            'measurement_unit_id' => $line->measurementUnitId,
            'location_id' => $line->locationId,
            'lot_id' => null,
        ]])[0];

        if ($lots === []) {
            return $resolved;
        }

        $baseSystem = round(array_sum(array_column($lots, 'base_system')), 4);

        $value = array_sum(array_map(
            static fn (array $lot): float => $lot['base_system'] * $lot['average'],
            $lots,
        ));

        return [
            'factor' => $resolved['factor'],
            'system' => round(array_sum(array_column($lots, 'system')), 4),
            'base_system' => $baseSystem,
            /** Sin saldo en los lotes no hay nada que ponderar: vale el de la ubicación. */
            'average' => $baseSystem > 0.0 ? round($value / $baseSystem, 6) : $resolved['average'],
        ];
    }

    /**
     * La misma resolución partiendo de la clave desnuda, para poder repetirla
     * sobre las líneas ya guardadas: al confirmar hay que volver a preguntar
     * qué dice el sistema, y ahí ya no hay comando, hay filas.
     *
     * @param  array<int, array{item_id: string, measurement_unit_id: string, location_id: ?string, lot_id: ?string}>  $keys
     * @return array<int, array{factor: float, system: float, base_system: float, average: float}>
     */
    public function resolveForKeys(?string $companyId, string $warehouseId, array $keys): array
    {
        $items = $this->itemsOf($companyId, array_column($keys, 'item_id'));
        $resolved = [];

        foreach ($keys as $index => $key) {
            $item = $items[$key['item_id']] ?? null;
            $factor = $this->factorFor($item, $key['measurement_unit_id']);
            $balance = $this->balance($companyId, $warehouseId, $key);

            $resolved[$index] = [
                'factor' => $factor,
                /** La existencia se guarda en unidad base; la línea cuenta en la suya. */
                'system' => $factor > 0.0 ? round($balance['quantity'] / $factor, 4) : 0.0,
                'base_system' => round($balance['quantity'], 4),
                'average' => $this->averageOf($balance, $item),
            ];
        }

        return $resolved;
    }

    /**
     * Saldo vivo de la clave que la línea cuenta, en unidad base.
     *
     * @param  array{item_id: string, location_id: ?string, lot_id: ?string}  $key
     * @return array{quantity: float, value: float}
     */
    private function balance(?string $companyId, string $warehouseId, array $key): array
    {
        $result = $this->stocks->search(new SearchItemStockCommand(
            filters: [
                'item_id' => $key['item_id'],
                'warehouse_id' => $warehouseId,
                'location_id' => $key['location_id'],
                'lot_id' => $key['lot_id'],
                'status' => 'active',
            ],
            limit: PHP_INT_MAX,
            companyId: $companyId,
        ));

        $quantity = 0.0;
        $value = 0.0;

        foreach ($result['data'] as $stock) {
            /** @var ItemStock $stock */
            $quantity += (float) $stock->quantity;
            $value += (float) $stock->total_value;
        }

        return ['quantity' => round($quantity, 4), 'value' => round($value, 2)];
    }

    /**
     * Promedio con el que se valora el ajuste. Sale del saldo que se está
     * contando; sin existencia sobre la que ponderar —un sobrante de algo que
     * el sistema daba por agotado— se usa el promedio del maestro de artículos.
     *
     * @param  array{quantity: float, value: float}  $balance
     */
    private function averageOf(array $balance, ?Item $item): float
    {
        if ($balance['quantity'] > 0.0) {
            return round($balance['value'] / $balance['quantity'], 6);
        }

        return round((float) ($item?->average_cost ?? 0), 6);
    }

    /**
     * Factor con el que la unidad de la línea se convierte a la unidad base.
     * Una unidad sin registrar cae en 1, que es lo que ya rechazó el Request.
     */
    private function factorFor(?Item $item, string $measurementUnitId): float
    {
        if (! $item instanceof Item) {
            return 1.0;
        }

        foreach ($item->units as $unit) {
            /** @var ItemUnit $unit */
            if ($unit->measurement_unit_id === $measurementUnitId) {
                return (float) $unit->conversion_factor;
            }
        }

        return 1.0;
    }

    /**
     * Artículos de las líneas, indexados por id.
     *
     * @param  array<int, string>  $itemIds
     * @return array<string, Item>
     */
    private function itemsOf(?string $companyId, array $itemIds): array
    {
        $items = [];

        foreach (array_unique($itemIds) as $itemId) {
            $item = $this->items->findById($itemId, $companyId);

            if ($item instanceof Item) {
                $items[$itemId] = $item;
            }
        }

        return $items;
    }
}
