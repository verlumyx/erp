<?php

declare(strict_types=1);

namespace App\Modules\Entry\Services;

use App\Modules\Entry\Commands\WriteEntryLineLotCommand;
use App\Modules\Entry\Commands\WriteEntryLineSerialCommand;
use App\Modules\Entry\Models\Entry;
use App\Modules\Entry\Models\EntryLine;
use App\Modules\Entry\Models\EntryLineLot;
use App\Modules\Entry\Models\EntryLineSerial;
use App\Modules\Entry\Repositories\Contracts\EntryRepositoryInterface;
use App\Modules\Item\Models\Item;
use App\Modules\ItemLot\Commands\CreateItemLotCommand;
use App\Modules\ItemLot\Commands\SearchItemLotCommand;
use App\Modules\ItemLot\Models\ItemLot;
use App\Modules\ItemLot\Repositories\Contracts\ItemLotRepositoryInterface;
use App\Modules\ItemLot\Services\ItemLotCreateService;
use App\Modules\ItemSerial\Commands\CreateItemSerialCommand;
use App\Modules\ItemSerial\Commands\SearchItemSerialCommand;
use App\Modules\ItemSerial\Models\ItemSerial;
use App\Modules\ItemSerial\Repositories\Contracts\ItemSerialRepositoryInterface;
use App\Modules\ItemSerial\Services\ItemSerialCreateService;
use Illuminate\Support\Str;

/**
 * El momento en que el lote y las series del proveedor se convierten en
 * registros del sistema.
 *
 * La pantalla captura un número de lote y una lista de series —lo que viene
 * impreso en la caja—, no ids: al confirmar la entrada esos números se buscan
 * en el maestro y, si no existen, se crean. Nacen aquí y no al guardar el
 * borrador porque hasta que la entrada no se confirma la mercancía no existe
 * todavía, y un lote sin mercancía detrás solo ensucia el maestro.
 *
 * El alta se hace por los servicios de sus módulos, no escribiendo sus tablas:
 * así las invariantes del lote y de la serie —que el artículo las admita, que
 * el número esté libre, que la serie sea de un lote del mismo artículo— valen
 * también cuando quien las crea es una entrada.
 */
class EntryTraceabilityService
{
    public function __construct(
        private readonly EntryRepositoryInterface $repository,
        private readonly ItemLotRepositoryInterface $lots,
        private readonly ItemLotCreateService $createLot,
        private readonly ItemSerialRepositoryInterface $serials,
        private readonly ItemSerialCreateService $createSerial,
    ) {}

    /**
     * El plan con el que la línea entra al kardex: qué lote, qué serie y cuánta
     * unidad base lleva cada asiento.
     *
     * Un artículo serializado entra unidad por unidad —cada serie es un asiento
     * de una—; uno con lote entra un asiento por lote; uno sin trazabilidad, en
     * un solo asiento. Lo rechazado se reparte entre los lotes en proporción a
     * lo que trajo cada uno: nadie decidió de qué caja salía lo malo, así que
     * el descuento no puede caer entero sobre la primera.
     *
     * @return array<int, array{lot: ?ItemLot, serial: ?ItemSerial, baseQuantity: float}>
     */
    public function resolve(Entry $entry, EntryLine $line, ?Item $item): array
    {
        $accepted = $line->baseReceivedQuantity();

        if ($accepted <= 0.0) {
            return [];
        }

        $lots = $this->resolveLots($entry, $line, $item);
        $serials = $this->resolveSerials($entry, $line, $item, $lots);

        if ($serials !== []) {
            return array_map(
                static fn (array $serial): array => [
                    'lot' => $serial['lot'],
                    'serial' => $serial['serial'],
                    'baseQuantity' => 1.0,
                ],
                $serials,
            );
        }

        if ($lots === []) {
            return [['lot' => null, 'serial' => null, 'baseQuantity' => $accepted]];
        }

        return $this->spread($lots, $accepted);
    }

    /**
     * Los lotes de la línea, ya registrados en el maestro.
     *
     * Una fila que ya trae `lot_id` manda; una que solo trae el número del
     * proveedor se busca y se crea si hace falta. El id resuelto se escribe en
     * la fila, de modo que la entrada quede apuntando al lote que movió y no al
     * papel del proveedor.
     *
     * @return array<string, array{row: EntryLineLot, lot: ItemLot}>
     */
    private function resolveLots(Entry $entry, EntryLine $line, ?Item $item): array
    {
        if (! $item instanceof Item || ! in_array($item->type, ItemLot::TRACKABLE_ITEM_TYPES, true)) {
            return [];
        }

        $resolved = [];

        foreach ($line->lots as $row) {
            /** @var EntryLineLot $row */
            if ($row->status !== 'active') {
                continue;
            }

            $lot = filled($row->lot_id)
                ? $this->lots->findById($row->lot_id, $entry->company_id)
                : $this->resolveLotByNumber($entry, $line, $row);

            if (! $lot instanceof ItemLot) {
                continue;
            }

            $this->repository->writeLineLot($row, new WriteEntryLineLotCommand(
                lotId: $lot->id,
                lotNumber: $lot->lot_number,
            ));

            $resolved[$row->id] = ['row' => $row, 'lot' => $lot];
        }

        return $resolved;
    }

    /** El lote del número que puso el proveedor: el que ya existe, o uno nuevo. */
    private function resolveLotByNumber(Entry $entry, EntryLine $line, EntryLineLot $row): ?ItemLot
    {
        if (blank($row->lot_number)) {
            return null;
        }

        return $this->findLotByNumber($entry->company_id, $line->item_id, (string) $row->lot_number)
            ?? $this->createLot->execute(new CreateItemLotCommand(
                id: (string) Str::uuid7(),
                companyId: (string) $entry->company_id,
                itemId: $line->item_id,
                lotNumber: (string) $row->lot_number,
                createdBy: (string) $entry->created_by,
                expiresAt: $row->expires_at?->toDateString(),
                supplierId: $entry->supplier_id,
            ));
    }

    /**
     * Las series de la línea, creadas si el proveedor las estrena. Una serie ya
     * registrada vuelve a usarse tal cual: es la misma unidad física que en su
     * día salió y ahora regresa, no una nueva.
     *
     * @param  array<string, array{row: EntryLineLot, lot: ItemLot}>  $lots
     * @return array<int, array{lot: ?ItemLot, serial: ItemSerial}>
     */
    private function resolveSerials(Entry $entry, EntryLine $line, ?Item $item, array $lots): array
    {
        if (! $item instanceof Item || $item->type !== ItemSerial::TRACKABLE_ITEM_TYPE) {
            return [];
        }

        /** Con un solo lote no hace falta que la serie diga de cuál sale. */
        $onlyLot = count($lots) === 1 ? reset($lots)['lot'] : null;

        $serials = [];

        foreach ($line->serials as $row) {
            /** @var EntryLineSerial $row */
            if ($row->status !== 'active' || blank($row->serial_number)) {
                continue;
            }

            $lot = $lots[$row->entry_line_lot_id]['lot'] ?? $onlyLot;

            $serial = filled($row->serial_id)
                ? $this->serials->findById($row->serial_id, $entry->company_id)
                : null;

            $serial ??= $this->findSerialByNumber($entry->company_id, $line->item_id, (string) $row->serial_number)
                ?? $this->createSerial->execute(new CreateItemSerialCommand(
                    id: (string) Str::uuid7(),
                    companyId: (string) $entry->company_id,
                    itemId: $line->item_id,
                    serialNumber: (string) $row->serial_number,
                    createdBy: (string) $entry->created_by,
                    lotId: $lot?->id,
                    warehouseId: $entry->warehouse_id,
                ));

            $this->repository->writeLineSerial($row, new WriteEntryLineSerialCommand(
                serialId: $serial->id,
                serialNumber: $serial->serial_number,
            ));

            $serials[] = ['lot' => $lot, 'serial' => $serial];
        }

        return $serials;
    }

    /**
     * Reparte lo aceptado entre los lotes en proporción a lo que trajo cada
     * uno. El último absorbe el redondeo, para que la suma de los asientos sea
     * exactamente lo que entra al inventario.
     *
     * @param  array<string, array{row: EntryLineLot, lot: ItemLot}>  $lots
     * @return array<int, array{lot: ?ItemLot, serial: ?ItemSerial, baseQuantity: float}>
     */
    private function spread(array $lots, float $accepted): array
    {
        $total = round(array_sum(array_map(
            static fn (array $entry): float => (float) $entry['row']->base_quantity,
            $lots,
        )), 4);

        if ($total <= 0.0) {
            return [];
        }

        $plan = [];
        $assigned = 0.0;
        $last = count($lots) - 1;

        foreach (array_values($lots) as $position => $entry) {
            $quantity = $position === $last
                ? round($accepted - $assigned, 4)
                : round((float) $entry['row']->base_quantity * $accepted / $total, 4);

            $assigned = round($assigned + $quantity, 4);

            if ($quantity <= 0.0) {
                continue;
            }

            $plan[] = ['lot' => $entry['lot'], 'serial' => null, 'baseQuantity' => $quantity];
        }

        return $plan;
    }

    /**
     * El filtro del repositorio busca por coincidencia parcial —es el del
     * select remoto—, así que la comparación exacta se hace aquí: `L-12` y
     * `L-120` no son el mismo lote.
     */
    private function findLotByNumber(?string $companyId, string $itemId, string $lotNumber): ?ItemLot
    {
        $result = $this->lots->search(new SearchItemLotCommand(
            filters: ['item_id' => $itemId, 'lot_number' => $lotNumber],
            limit: 50,
            companyId: $companyId,
        ));

        foreach ($result['data'] as $lot) {
            if ($lot->lot_number === $lotNumber) {
                return $lot;
            }
        }

        return null;
    }

    private function findSerialByNumber(?string $companyId, string $itemId, string $serialNumber): ?ItemSerial
    {
        $result = $this->serials->search(new SearchItemSerialCommand(
            filters: ['item_id' => $itemId, 'serial_number' => $serialNumber],
            limit: 50,
            companyId: $companyId,
        ));

        foreach ($result['data'] as $serial) {
            if ($serial->serial_number === $serialNumber) {
                return $serial;
            }
        }

        return null;
    }
}
