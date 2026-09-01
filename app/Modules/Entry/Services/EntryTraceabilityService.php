<?php

declare(strict_types=1);

namespace App\Modules\Entry\Services;

use App\Modules\Entry\Commands\WriteEntryLineTraceabilityCommand;
use App\Modules\Entry\Models\Entry;
use App\Modules\Entry\Models\EntryLine;
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
     * Resuelve el lote de la línea y devuelve las series ya registradas, en el
     * mismo orden en que el usuario las capturó.
     *
     * @return array<int, ItemSerial>
     */
    public function resolve(Entry $entry, EntryLine $line, ?Item $item): array
    {
        $lot = $this->resolveLot($entry, $line, $item);

        return $this->resolveSerials($entry, $line, $item, $lot);
    }

    /**
     * El lote con el que la línea entra. Si la línea ya trae uno elegido a
     * mano, ese manda; si trae el número que puso el proveedor, se busca y se
     * crea si hace falta. El id resuelto se escribe en la línea, de modo que la
     * entrada quede apuntando al lote que movió y no al papel del proveedor.
     */
    private function resolveLot(Entry $entry, EntryLine $line, ?Item $item): ?ItemLot
    {
        if (filled($line->lot_id)) {
            return $this->lots->findById($line->lot_id, $entry->company_id);
        }

        if (blank($line->lot_number) || ! $item instanceof Item) {
            return null;
        }

        if (! in_array($item->type, ItemLot::TRACKABLE_ITEM_TYPES, true)) {
            return null;
        }

        $lot = $this->findLotByNumber($entry->company_id, $line->item_id, (string) $line->lot_number)
            ?? $this->createLot->execute(new CreateItemLotCommand(
                id: (string) Str::uuid7(),
                companyId: (string) $entry->company_id,
                itemId: $line->item_id,
                lotNumber: (string) $line->lot_number,
                createdBy: (string) $entry->created_by,
                expiresAt: $line->expires_at?->toDateString(),
                supplierId: $entry->supplier_id,
            ));

        $this->repository->writeLineTraceability($line, new WriteEntryLineTraceabilityCommand(
            lotId: $lot->id,
            lotNumber: $lot->lot_number,
        ));

        return $lot;
    }

    /**
     * Las series de la línea, creadas si el proveedor las estrena. Una serie ya
     * registrada vuelve a usarse tal cual: es la misma unidad física que en su
     * día salió y ahora regresa, no una nueva.
     *
     * @return array<int, ItemSerial>
     */
    private function resolveSerials(Entry $entry, EntryLine $line, ?Item $item, ?ItemLot $lot): array
    {
        $numbers = $line->serial_numbers ?? [];

        if ($numbers === [] || ! $item instanceof Item || $item->type !== ItemSerial::TRACKABLE_ITEM_TYPE) {
            return [];
        }

        $serials = [];

        foreach ($numbers as $number) {
            $serials[] = $this->findSerialByNumber($entry->company_id, $line->item_id, (string) $number)
                ?? $this->createSerial->execute(new CreateItemSerialCommand(
                    id: (string) Str::uuid7(),
                    companyId: (string) $entry->company_id,
                    itemId: $line->item_id,
                    serialNumber: (string) $number,
                    createdBy: (string) $entry->created_by,
                    lotId: $lot?->id,
                    warehouseId: $entry->warehouse_id,
                ));
        }

        return $serials;
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
