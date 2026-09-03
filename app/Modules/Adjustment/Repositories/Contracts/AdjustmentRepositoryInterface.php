<?php

declare(strict_types=1);

namespace App\Modules\Adjustment\Repositories\Contracts;

use App\Modules\Adjustment\Commands\CreateAdjustmentCommand;
use App\Modules\Adjustment\Commands\SearchAdjustmentCommand;
use App\Modules\Adjustment\Commands\UpdateAdjustmentCommand;
use App\Modules\Adjustment\Commands\UpdateStatusAdjustmentCommand;
use App\Modules\Adjustment\Commands\WriteAdjustmentLineCostCommand;
use App\Modules\Adjustment\Models\Adjustment;
use App\Modules\Adjustment\Models\AdjustmentLine;
use App\Modules\Adjustment\Models\AdjustmentLineLot;

interface AdjustmentRepositoryInterface
{
    /**
     * @param  array<int, array{factor: float, system: float, base_system: float, average: float, lots: array<string, array{system: float, base_system: float, average: float}>}>  $stock
     *                                                                                                                                                                             Existencia y promedio de cada línea y de cada uno de sus
     *                                                                                                                                                                             lotes, ya resueltos por `AdjustmentStockService`.
     */
    public function create(CreateAdjustmentCommand $command, array $stock): void;

    public function findById(string $id, ?string $companyId = null): ?Adjustment;

    public function findOrFail(string $id, ?string $companyId = null): Adjustment;

    /**
     * @param  array<int, array{factor: float, system: float, base_system: float, average: float, lots: array<string, array{system: float, base_system: float, average: float}>}>  $stock
     */
    public function update(Adjustment $model, UpdateAdjustmentCommand $command, array $stock): void;

    public function updateStatus(Adjustment $model, UpdateStatusAdjustmentCommand $command): void;

    /** @return array{ data: Adjustment[], total: int } */
    public function search(SearchAdjustmentCommand $command): array;

    /**
     * Líneas activas del ajuste, con lo que el kardex necesita para moverlas:
     * el artículo con sus unidades, la unidad de la línea y la trazabilidad que
     * parte el movimiento en varios.
     *
     * @return array<int, AdjustmentLine>
     */
    public function activeLines(Adjustment $model): array;

    /**
     * Escribe el costo con el que el kardex valoró de verdad una línea. Solo lo
     * llama `AdjustmentPostingService`, al confirmar el ajuste.
     */
    public function writeLineCost(
        AdjustmentLine $line,
        WriteAdjustmentLineCostCommand $command,
    ): AdjustmentLine;

    /**
     * Lo mismo para uno de los lotes de la línea: el kardex lo valora aparte,
     * porque cada lote sale o entra a su propio costo.
     */
    public function writeLotCost(
        AdjustmentLineLot $lot,
        WriteAdjustmentLineCostCommand $command,
    ): AdjustmentLineLot;

    /**
     * Recalcula los totales de la cabecera a partir de las líneas ya escritas.
     * Se llama después de aplicar el ajuste, cuando los costos dejaron de ser
     * una estimación.
     */
    public function refreshTotals(Adjustment $model): Adjustment;
}
