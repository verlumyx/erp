<?php

declare(strict_types=1);

namespace App\Modules\Import\Repositories\Contracts;

use App\Modules\ExchangeRate\Commands\DocumentRatesData;
use App\Modules\Import\Commands\CreateImportCommand;
use App\Modules\Import\Commands\ImportCostingData;
use App\Modules\Import\Commands\SearchImportCommand;
use App\Modules\Import\Commands\UpdateImportCommand;
use App\Modules\Import\Commands\UpdateStatusImportCommand;
use App\Modules\Import\Models\Import;
use App\Modules\Import\Models\ImportLine;

interface ImportRepositoryInterface
{
    /**
     * El reparto llega ya resuelto por `ImportCostingService`: la pantalla no
     * captura ni los ítems ni lo que le toca a cada uno.
     */
    public function create(CreateImportCommand $command, DocumentRatesData $rates, ImportCostingData $costing): void;

    public function findById(string $id, ?string $companyId = null): ?Import;

    public function findOrFail(string $id, ?string $companyId = null): Import;

    public function update(Import $model, UpdateImportCommand $command, DocumentRatesData $rates, ImportCostingData $costing): void;

    public function updateStatus(Import $model, UpdateStatusImportCommand $command): void;

    /** @return array{ data: Import[], total: int } */
    public function search(SearchImportCommand $command): array;

    /**
     * Ítems vivos del expediente, con lo que el ajuste necesita para
     * revalorizar: el artículo con sus unidades y el reparto por lote.
     *
     * @return array<int, ImportLine>
     */
    public function activeLines(Import $model): array;

    /**
     * Recepciones vivas del expediente.
     *
     * @return array<int, string> Ids de entrada.
     */
    public function activeEntryIds(Import $model): array;

    /**
     * Entradas ya tomadas por otro expediente vivo. Una entrada no se costea
     * dos veces: el gasto que llega en dos tandas se rehace en un expediente
     * nuevo, no se suma a uno cerrado.
     *
     * @param  array<int, string>  $entryIds
     * @return array<int, string> Ids de entrada ya comprometidas.
     */
    public function entriesTakenElsewhere(
        ?string $companyId,
        array $entryIds,
        ?string $exceptImportId = null,
    ): array;

    /**
     * Reescribe los ítems y los totales con un reparto recién resuelto, sin
     * tocar ni los cargos ni las tasas ya congeladas. Solo lo llama la
     * confirmación: entre el último borrador y la firma la bodega siguió
     * trabajando.
     */
    public function writeCosting(Import $model, ImportCostingData $costing): Import;

    /**
     * Cuelga del expediente el ajuste de revaluación que acaba de generar.
     * Solo lo llama `ImportMirrorAdjustmentService`, al confirmar.
     */
    public function writeAdjustment(Import $model, ?string $adjustmentId): Import;

    /**
     * Cierra —o reabre— el expediente. Lo escribe el ajuste al confirmarse o
     * al anularse: cerrarlo no es una decisión de la pantalla.
     */
    public function writeSettlement(Import $model, bool $settled): Import;
}
