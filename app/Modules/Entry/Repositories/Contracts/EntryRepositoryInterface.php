<?php

declare(strict_types=1);

namespace App\Modules\Entry\Repositories\Contracts;

use App\Modules\Entry\Commands\CreateEntryCommand;
use App\Modules\Entry\Commands\EntryLineData;
use App\Modules\Entry\Commands\SearchEntryCommand;
use App\Modules\Entry\Commands\UpdateEntryCommand;
use App\Modules\Entry\Commands\UpdateStatusEntryCommand;
use App\Modules\Entry\Commands\WriteEntryLineLotCommand;
use App\Modules\Entry\Commands\WriteEntryLineSerialCommand;
use App\Modules\Entry\Models\Entry;
use App\Modules\Entry\Models\EntryLine;
use App\Modules\Entry\Models\EntryLineLot;
use App\Modules\Entry\Models\EntryLineSerial;
use App\Modules\ExchangeRate\Commands\DocumentRatesData;

interface EntryRepositoryInterface
{
    /**
     * Las líneas llegan aparte del comando porque antes pasan por
     * `EntryPricingService`: la pantalla no captura el costo, lo pone el
     * sistema. Mismo criterio que el `unitCosts` de los despachos.
     *
     * @param  array<int, EntryLineData>  $lines
     */
    public function create(CreateEntryCommand $command, DocumentRatesData $rates, array $lines): void;

    public function findById(string $id, ?string $companyId = null): ?Entry;

    public function findOrFail(string $id, ?string $companyId = null): Entry;

    /**
     * @param  array<int, EntryLineData>  $lines
     */
    public function update(Entry $model, UpdateEntryCommand $command, DocumentRatesData $rates, array $lines): void;

    public function updateStatus(Entry $model, UpdateStatusEntryCommand $command): void;

    /** @return array{ data: Entry[], total: int } */
    public function search(SearchEntryCommand $command): array;

    /**
     * Líneas activas de la entrada, con lo que el kardex necesita para valorar
     * el ingreso: el artículo, su unidad y la trazabilidad con la que se parte
     * el asiento.
     *
     * @return array<int, EntryLine>
     */
    public function activeLines(Entry $model): array;

    /**
     * Escribe el lote ya resuelto de una fila de trazabilidad. Solo lo llama
     * `EntryTraceabilityService`, al confirmar la entrada: hasta entonces la
     * fila solo conoce el número que puso el proveedor.
     */
    public function writeLineLot(EntryLineLot $row, WriteEntryLineLotCommand $command): EntryLineLot;

    /** Escribe la serie ya resuelta de una fila de trazabilidad. */
    public function writeLineSerial(EntryLineSerial $row, WriteEntryLineSerialCommand $command): EntryLineSerial;

    /**
     * Cantidad ya recibida de cada línea de documento origen por entradas
     * distintas de la indicada. Es lo que limita cuánto puede recibir una línea
     * nueva sin pasarse de lo pedido.
     *
     * @param  array<int, string>  $sourceLineIds
     * @return array<string, float> Id de la línea origen → cantidad recibida.
     */
    public function receivedQuantities(array $sourceLineIds, ?string $exceptEntryId = null): array;

    /**
     * ¿Ya hay una entrada de inventario inicial viva para ese artículo en esa
     * bodega? El inventario inicial se carga una sola vez.
     *
     * @param  array<int, string>  $itemIds
     * @return array<int, string> Ids de artículo que ya tienen inventario inicial.
     */
    public function itemsWithInitialEntry(
        ?string $companyId,
        string $warehouseId,
        array $itemIds,
        ?string $exceptEntryId = null,
    ): array;
}
