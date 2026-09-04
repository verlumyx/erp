<?php

declare(strict_types=1);

namespace App\Modules\Import\Services;

use App\Modules\Adjustment\Services\AdjustmentStockService;
use App\Modules\Entry\Models\Entry;
use App\Modules\Entry\Models\EntryLine;
use App\Modules\Entry\Models\EntryLineLot;
use App\Modules\Entry\Repositories\Contracts\EntryRepositoryInterface;
use App\Modules\ExchangeRate\Commands\DocumentRatesData;
use App\Modules\ExchangeRate\Services\Contracts\ExchangeRateResolverInterface;
use App\Modules\Import\Commands\ImportCostData;
use App\Modules\Import\Commands\ImportCostingData;
use App\Modules\Import\Models\Import;
use App\Modules\Import\Models\ImportCost;
use App\Modules\Import\Models\ImportEntry;
use App\Modules\Import\Models\ImportLine;
use App\Modules\Item\Models\Item;

/**
 * El reparto: qué le toca a cada cosa que llegó de lo que costó traerla.
 *
 * Es el único sitio del módulo donde se calcula, y no recibe un solo número
 * desde la pantalla. Los cargos llegan en su moneda y se convierten a la del
 * expediente con el catálogo de tasas; los ítems ni se capturan: se derivan de
 * las líneas vivas de las recepciones, con lo que la inspección aceptó y con el
 * costo con el que la entrada las metió al kardex.
 *
 * El reparto se hace sobre **lo que de verdad absorbe**: un lote cuando el
 * artículo los lleva —el kardex guarda su saldo por lote, y dos cajas de la
 * misma línea pueden tener distinto saldo vivo—, y la línea entera cuando no.
 * La línea con lotes es después la suma de los suyos, nunca al revés.
 */
class ImportCostingService
{
    public function __construct(
        private readonly EntryRepositoryInterface $entries,
        private readonly AdjustmentStockService $stock,
        private readonly ExchangeRateResolverInterface $rates,
    ) {}

    /**
     * @param  array<int, ImportCostData>  $costs
     * @param  array<int, string>  $entryIds  Recepciones activas, en su orden.
     * @param  array<string, string>  $lineStatuses  Id de la línea de entrada → estado.
     */
    public function resolve(
        ?string $companyId,
        string $warehouseId,
        string $importDate,
        string $allocationMethod,
        DocumentRatesData $documentRates,
        array $costs,
        array $entryIds,
        array $lineStatuses = [],
    ): ImportCostingData {
        $charges = $this->charges($companyId, $importDate, $documentRates, $costs);

        $totalCharges = round(array_sum(array_map(
            static fn (int $index): float => $costs[$index]->status === 'active'
                ? $charges[$index]['converted_amount']
                : 0.0,
            array_keys($charges),
        )), 2);

        return $this->build(
            $companyId,
            $warehouseId,
            $allocationMethod,
            $charges,
            $totalCharges,
            $entryIds,
            $lineStatuses,
        );
    }

    /**
     * El mismo reparto sobre un expediente ya guardado.
     *
     * Lo llama la confirmación: entre el último borrador y la firma la bodega
     * siguió trabajando, así que el saldo vivo y el promedio se vuelven a
     * preguntar. Las tasas no: esas se congelaron al guardar, y volver a
     * resolverlas descongelaría el documento.
     */
    public function recost(Import $import): ImportCostingData
    {
        $charges = [];
        $totalCharges = 0.0;

        foreach ($import->costs as $index => $cost) {
            /** @var ImportCost $cost */
            $charges[$index] = [
                'exchange_rate' => round((float) $cost->exchange_rate, 8),
                'converted_amount' => round((float) $cost->converted_amount, 2),
            ];

            if ($cost->status === 'active') {
                $totalCharges += (float) $cost->converted_amount;
            }
        }

        $entryIds = [];
        $lineStatuses = [];

        foreach ($import->entries as $entry) {
            /** @var ImportEntry $entry */
            if ($entry->status === 'active') {
                $entryIds[] = (string) $entry->entry_id;
            }
        }

        foreach ($import->lines as $line) {
            /** @var ImportLine $line */
            $lineStatuses[(string) $line->entry_line_id] = $line->status;
        }

        return $this->build(
            $import->company_id,
            (string) $import->warehouse_id,
            (string) $import->allocation_method,
            $charges,
            round($totalCharges, 2),
            $entryIds,
            $lineStatuses,
        );
    }

    /**
     * El reparto propiamente dicho, con los cargos ya convertidos.
     *
     * @param  array<int, array{exchange_rate: float, converted_amount: float}>  $charges
     * @param  array<int, string>  $entryIds
     * @param  array<string, string>  $lineStatuses
     */
    private function build(
        ?string $companyId,
        string $warehouseId,
        string $allocationMethod,
        array $charges,
        float $totalCharges,
        array $entryIds,
        array $lineStatuses,
    ): ImportCostingData {
        $lines = $this->lines($companyId, $warehouseId, $allocationMethod, $entryIds, $lineStatuses);
        $lines = $this->allocate($lines, $totalCharges);

        $totalBaseValue = 0.0;
        $capitalized = 0.0;
        $variance = 0.0;

        foreach ($lines as $line) {
            if ($line['status'] !== 'active') {
                continue;
            }

            $totalBaseValue += $line['base_value'];
            $capitalized += $line['capitalized_amount'];
            $variance += $line['variance_amount'];
        }

        return new ImportCostingData(
            charges: $charges,
            lines: $lines,
            totalCharges: $totalCharges,
            totalBaseValue: round($totalBaseValue, 2),
            totalLandedValue: round($totalBaseValue + $totalCharges, 2),
            capitalizedAmount: round($capitalized, 2),
            varianceAmount: round($variance, 2),
        );
    }

    /**
     * Cada cargo llevado a la moneda del expediente. La tasa sale del catálogo
     * y no del formulario, como en el resto de los documentos: lo que se
     * captura es el importe en la moneda en la que se cobró.
     *
     * @param  array<int, ImportCostData>  $costs
     * @return array<int, array{exchange_rate: float, converted_amount: float}>
     */
    private function charges(
        ?string $companyId,
        string $importDate,
        DocumentRatesData $documentRates,
        array $costs,
    ): array {
        $charges = [];

        foreach ($costs as $index => $cost) {
            $rate = $cost->currency === $documentRates->currency
                ? 1.0
                : $this->rates->convert(
                    1.0,
                    $cost->currency,
                    $documentRates->currency,
                    (string) $companyId,
                    $importDate,
                    $documentRates->rateType,
                );

            $charges[$index] = [
                'exchange_rate' => round($rate, 8),
                'converted_amount' => round($cost->amount * $rate, 2),
            ];
        }

        return $charges;
    }

    /**
     * Los ítems del expediente, derivados de las recepciones.
     *
     * Un artículo `service` o `non_inventoried` no entra: no lleva existencia,
     * así que no hay costo que reexpresar. Lo rechazado en la inspección
     * tampoco: no ingresó al inventario, así que no carga con gastos.
     *
     * @param  array<int, string>  $entryIds
     * @param  array<string, string>  $lineStatuses
     * @return array<int, array<string, mixed>>
     */
    private function lines(
        ?string $companyId,
        string $warehouseId,
        string $allocationMethod,
        array $entryIds,
        array $lineStatuses,
    ): array {
        $rows = [];

        foreach ($entryIds as $entryId) {
            $entry = $this->entries->findById($entryId, $companyId);

            if (! $entry instanceof Entry) {
                continue;
            }

            foreach ($this->entries->activeLines($entry) as $line) {
                $row = $this->lineOf($line, $allocationMethod, $lineStatuses);

                if ($row !== null) {
                    $rows[] = $row;
                }
            }
        }

        return $this->resolveBalances($companyId, $warehouseId, $rows);
    }

    /**
     * Lo que una línea de entrada aporta al expediente, todavía sin saldo vivo
     * ni promedio: eso se resuelve de una vez para todas en `resolveBalances()`.
     *
     * @param  array<string, string>  $lineStatuses
     * @return array<string, mixed>|null
     */
    private function lineOf(EntryLine $line, string $allocationMethod, array $lineStatuses): ?array
    {
        $item = $line->item;

        if (! $item instanceof Item || ! $item->movesStock()) {
            return null;
        }

        $accepted = $line->baseReceivedQuantity();

        if ($accepted <= 0.0) {
            return null;
        }

        $unitCost = round((float) $line->landed_cost, 6);

        return [
            'entry_line_id' => (string) $line->id,
            'item_id' => (string) $line->item_id,
            'measurement_unit_id' => (string) $line->measurement_unit_id,
            'location_id' => $line->location_id,
            'base_quantity' => $accepted,
            'unit_cost' => $unitCost,
            'base_value' => round($accepted * $unitCost, 2),
            'status' => $lineStatuses[$line->id] ?? 'active',
            'allocation_base' => $this->allocationBase($allocationMethod, $accepted, $accepted * $unitCost, $item),
            'lots' => $this->lotsOf($line, $allocationMethod, $accepted, $unitCost, $item),
        ];
    }

    /**
     * Las cajas con las que llegó la línea, con lo aceptado repartido entre
     * ellas en proporción a lo que trajo cada una: nadie decidió de qué caja
     * salía lo que la inspección rechazó. La última absorbe el redondeo, para
     * que los lotes sumen exactamente lo aceptado por la línea.
     *
     * @return array<int, array<string, mixed>>
     */
    private function lotsOf(
        EntryLine $line,
        string $allocationMethod,
        float $accepted,
        float $unitCost,
        Item $item,
    ): array {
        $rows = [];

        foreach ($line->lots as $lot) {
            /** @var EntryLineLot $lot */
            if ($lot->status === 'active' && filled($lot->lot_id)) {
                $rows[] = $lot;
            }
        }

        if ($rows === []) {
            return [];
        }

        $arrived = round(array_sum(array_map(
            static fn (EntryLineLot $lot): float => (float) $lot->base_quantity,
            $rows,
        )), 4);

        if ($arrived <= 0.0) {
            return [];
        }

        $lots = [];
        $assigned = 0.0;
        $last = count($rows) - 1;

        foreach ($rows as $position => $lot) {
            $quantity = $position === $last
                ? round($accepted - $assigned, 4)
                : round((float) $lot->base_quantity * $accepted / $arrived, 4);

            $assigned = round($assigned + $quantity, 4);

            if ($quantity <= 0.0) {
                continue;
            }

            $lots[] = [
                'entry_line_lot_id' => (string) $lot->id,
                'lot_id' => (string) $lot->lot_id,
                'base_quantity' => $quantity,
                'allocation_base' => $this->allocationBase($allocationMethod, $quantity, $quantity * $unitCost, $item),
            ];
        }

        return $lots;
    }

    /**
     * El número con el que reparte: el valor de lo que entró, su cantidad, su
     * peso o su volumen. Un artículo sin el dato registrado reparte por cero, y
     * el expediente se queda sin confirmar antes de llegar aquí.
     */
    private function allocationBase(string $method, float $quantity, float $value, Item $item): float
    {
        return round(match ($method) {
            'quantity' => $quantity,
            'weight' => $quantity * (float) $item->weight,
            'volume' => $quantity * (float) $item->volume,
            default => $value,
        }, 4);
    }

    /**
     * Lo que sigue en existencia de cada cosa que entró, y el promedio con el
     * que hoy se valora.
     *
     * Con lote la cuenta es exacta y se hace por lote. Sin lote es una
     * aproximación deliberada: bajo promedio ponderado las unidades no se
     * guardan por capa, así que se compara la existencia viva del artículo en
     * esa ubicación contra lo que entró. Dos líneas que comparten esa clave se
     * reparten el saldo por orden en vez de reclamarlo entera cada una: nunca
     * se capitaliza sobre unidades que ya no están.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function resolveBalances(?string $companyId, string $warehouseId, array $rows): array
    {
        $keys = [];
        $positions = [];

        foreach ($rows as $index => $row) {
            if ($row['lots'] === []) {
                $positions[] = ['line' => $index, 'lot' => null];
                $keys[] = $this->balanceKey($row, null);

                continue;
            }

            foreach (array_keys($row['lots']) as $lotIndex) {
                $positions[] = ['line' => $index, 'lot' => $lotIndex];
                $keys[] = $this->balanceKey($row, $row['lots'][$lotIndex]);
            }
        }

        $balances = $keys === []
            ? []
            : $this->stock->resolveForKeys($companyId, $warehouseId, $keys);

        /** Saldo que queda por repartir entre las filas que comparten clave. */
        $available = [];

        foreach ($positions as $position => $key) {
            $signature = $this->signatureOf($keys[$position]);
            $balance = $balances[$position] ?? ['base_system' => 0.0, 'average' => 0.0];

            $available[$signature] ??= round((float) $balance['base_system'], 4);

            $target = $key['lot'] === null
                ? $rows[$key['line']]
                : $rows[$key['line']]['lots'][$key['lot']];

            /** Una fila fuera del reparto no reclama saldo: no absorbe nada. */
            $remaining = $rows[$key['line']]['status'] === 'active'
                ? round(min((float) $target['base_quantity'], max($available[$signature], 0.0)), 4)
                : 0.0;

            $available[$signature] = round($available[$signature] - $remaining, 4);

            $resolved = [
                'remaining_quantity' => $remaining,
                'average_cost' => round((float) $balance['average'], 6),
            ];

            if ($key['lot'] === null) {
                $rows[$key['line']] = [...$rows[$key['line']], ...$resolved];

                continue;
            }

            $rows[$key['line']]['lots'][$key['lot']] = [
                ...$rows[$key['line']]['lots'][$key['lot']],
                ...$resolved,
            ];
        }

        return $this->rollUpLots($rows);
    }

    /**
     * La línea que lleva lotes es la suma de los suyos, también en el saldo
     * vivo. Su promedio es el de esos lotes ya ponderado: es la existencia que
     * el expediente está reexpresando, no todo lo que haya en la ubicación.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function rollUpLots(array $rows): array
    {
        foreach ($rows as $index => $row) {
            if ($row['lots'] === []) {
                continue;
            }

            $remaining = round(array_sum(array_column($row['lots'], 'remaining_quantity')), 4);

            $value = array_sum(array_map(
                static fn (array $lot): float => $lot['remaining_quantity'] * $lot['average_cost'],
                $row['lots'],
            ));

            $rows[$index]['remaining_quantity'] = $remaining;
            $rows[$index]['average_cost'] = $remaining > 0.0
                ? round($value / $remaining, 6)
                : round((float) ($row['lots'][0]['average_cost'] ?? 0.0), 6);
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, mixed>|null  $lot
     * @return array{item_id: string, measurement_unit_id: string, location_id: ?string, lot_id: ?string}
     */
    private function balanceKey(array $row, ?array $lot): array
    {
        return [
            'item_id' => $row['item_id'],
            'measurement_unit_id' => $row['measurement_unit_id'],
            'location_id' => $row['location_id'],
            'lot_id' => $lot['lot_id'] ?? null,
        ];
    }

    /**
     * @param  array{item_id: string, location_id: ?string, lot_id: ?string}  $key
     */
    private function signatureOf(array $key): string
    {
        return implode('|', [$key['item_id'], $key['location_id'] ?? '', $key['lot_id'] ?? '']);
    }

    /**
     * El reparto propiamente dicho: `ratio = total_charges / Σ allocation_base`
     * y `allocated_amount = allocation_base * ratio`.
     *
     * Se reparte sobre lo que absorbe —el lote cuando lo hay, la línea cuando
     * no—, y la última fila absorbe el redondeo para que lo repartido sume
     * exactamente lo que costó traer la mercancía. Unos gastos sin base sobre
     * la que repartirse se quedan fuera del costo en vez de inventar uno.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function allocate(array $rows, float $totalCharges): array
    {
        $targets = [];

        foreach ($rows as $index => $row) {
            if ($row['status'] !== 'active') {
                continue;
            }

            if ($row['lots'] === []) {
                $targets[] = ['line' => $index, 'lot' => null];

                continue;
            }

            foreach (array_keys($row['lots']) as $lotIndex) {
                $targets[] = ['line' => $index, 'lot' => $lotIndex];
            }
        }

        $total = round(array_sum(array_map(
            fn (array $target): float => (float) $this->targetOf($rows, $target)['allocation_base'],
            $targets,
        )), 4);

        $assigned = 0.0;
        $last = count($targets) - 1;

        foreach ($targets as $position => $target) {
            $base = (float) $this->targetOf($rows, $target)['allocation_base'];

            $allocated = $total <= 0.0
                ? 0.0
                : ($position === $last
                    ? round($totalCharges - $assigned, 2)
                    : round($base * $totalCharges / $total, 2));

            $assigned = round($assigned + $allocated, 2);

            $rows = $this->writeAllocation($rows, $target, $allocated);
        }

        return $this->settle($rows, $targets);
    }

    /**
     * Deja escrito en una fila lo que le tocó: el gasto, lo que sube cada
     * unidad, el costo nuevo y qué parte de todo eso llega al inventario.
     *
     * `new_unit_cost` no es el costo de la entrada más el gasto: es el promedio
     * vigente más `unit_delta`. Entre la entrada y el expediente pudo haber
     * entrado más mercancía a otro precio, y escribir el costo de aquella
     * línea borraría todo lo que pasó en medio.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array{line: int, lot: ?int}  $target
     * @return array<int, array<string, mixed>>
     */
    private function writeAllocation(array $rows, array $target, float $allocated): array
    {
        $row = $this->targetOf($rows, $target);

        $base = (float) $row['base_quantity'];
        $delta = $base > 0.0 ? round($allocated / $base, 6) : 0.0;
        $capitalized = round((float) $row['remaining_quantity'] * $delta, 2);

        $written = [
            ...$row,
            'allocated_amount' => $allocated,
            'unit_delta' => $delta,
            'new_unit_cost' => round((float) $row['average_cost'] + $delta, 6),
            'capitalized_amount' => $capitalized,
            'variance_amount' => round($allocated - $capitalized, 2),
        ];

        if ($target['lot'] === null) {
            $rows[$target['line']] = $written;

            return $rows;
        }

        $rows[$target['line']]['lots'][$target['lot']] = $written;

        return $rows;
    }

    /**
     * Cierra las filas: la línea con lotes toma la suma de los suyos, y la que
     * quedó fuera del reparto —inactiva, o de una línea inactiva— se queda en
     * ceros para que la pantalla enseñe que no absorbió nada.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, array{line: int, lot: ?int}>  $targets
     * @return array<int, array<string, mixed>>
     */
    private function settle(array $rows, array $targets): array
    {
        $allocatedLines = array_unique(array_column($targets, 'line'));

        foreach ($rows as $index => $row) {
            if (! in_array($index, $allocatedLines, true)) {
                $rows[$index] = [...$row, ...$this->zeroedAllocation($row), 'lots' => array_map(
                    fn (array $lot): array => [...$lot, ...$this->zeroedAllocation($lot)],
                    $row['lots'],
                )];

                continue;
            }

            if ($row['lots'] === []) {
                continue;
            }

            $rows[$index] = [...$row, ...$this->sumOfLots($row)];
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, float>
     */
    private function zeroedAllocation(array $row): array
    {
        $average = (float) ($row['average_cost'] ?? 0.0);

        return [
            'allocated_amount' => 0.0,
            'unit_delta' => 0.0,
            'new_unit_cost' => round($average, 6),
            'capitalized_amount' => 0.0,
            'variance_amount' => 0.0,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, float>
     */
    private function sumOfLots(array $row): array
    {
        $allocated = round(array_sum(array_column($row['lots'], 'allocated_amount')), 2);
        $base = round(array_sum(array_column($row['lots'], 'base_quantity')), 4);
        $delta = $base > 0.0 ? round($allocated / $base, 6) : 0.0;

        return [
            'allocation_base' => round(array_sum(array_column($row['lots'], 'allocation_base')), 4),
            'allocated_amount' => $allocated,
            'unit_delta' => $delta,
            'new_unit_cost' => round((float) $row['average_cost'] + $delta, 6),
            'capitalized_amount' => round(array_sum(array_column($row['lots'], 'capitalized_amount')), 2),
            'variance_amount' => round(array_sum(array_column($row['lots'], 'variance_amount')), 2),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array{line: int, lot: ?int}  $target
     * @return array<string, mixed>
     */
    private function targetOf(array $rows, array $target): array
    {
        return $target['lot'] === null
            ? $rows[$target['line']]
            : $rows[$target['line']]['lots'][$target['lot']];
    }
}
