<?php

declare(strict_types=1);

namespace App\Modules\Entry\Repositories\Contracts;

use App\Modules\Entry\Commands\CreateEntryCommand;
use App\Modules\Entry\Commands\SearchEntryCommand;
use App\Modules\Entry\Commands\UpdateEntryCommand;
use App\Modules\Entry\Commands\UpdateStatusEntryCommand;
use App\Modules\Entry\Commands\WriteEntryLineTraceabilityCommand;
use App\Modules\Entry\Models\Entry;
use App\Modules\Entry\Models\EntryLine;
use App\Modules\ExchangeRate\Commands\DocumentRatesData;

interface EntryRepositoryInterface
{
    public function create(CreateEntryCommand $command, DocumentRatesData $rates): void;

    public function findById(string $id, ?string $companyId = null): ?Entry;

    public function findOrFail(string $id, ?string $companyId = null): Entry;

    public function update(Entry $model, UpdateEntryCommand $command, DocumentRatesData $rates): void;

    public function updateStatus(Entry $model, UpdateStatusEntryCommand $command): void;

    /** @return array{ data: Entry[], total: int } */
    public function search(SearchEntryCommand $command): array;

    /**
     * Líneas activas de la entrada, con lo que el kardex necesita para valorar
     * el ingreso: el artículo y su unidad.
     *
     * @return array<int, EntryLine>
     */
    public function activeLines(Entry $model): array;

    /**
     * Escribe el lote ya resuelto de una línea. Solo lo llama
     * `EntryTraceabilityService`, al confirmar la entrada: hasta entonces la
     * línea solo conoce el número que puso el proveedor.
     */
    public function writeLineTraceability(
        EntryLine $line,
        WriteEntryLineTraceabilityCommand $command,
    ): EntryLine;

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
