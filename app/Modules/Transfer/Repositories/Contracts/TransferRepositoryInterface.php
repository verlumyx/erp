<?php

declare(strict_types=1);

namespace App\Modules\Transfer\Repositories\Contracts;

use App\Modules\Transfer\Commands\CreateTransferCommand;
use App\Modules\Transfer\Commands\SearchTransferCommand;
use App\Modules\Transfer\Commands\UpdateStatusTransferCommand;
use App\Modules\Transfer\Commands\UpdateTransferCommand;
use App\Modules\Transfer\Commands\WriteTransferLineCostCommand;
use App\Modules\Transfer\Commands\WriteTransferReceiptCommand;
use App\Modules\Transfer\Models\Transfer;
use App\Modules\Transfer\Models\TransferLine;

interface TransferRepositoryInterface
{
    /**
     * @param  array<int, float>  $unitCosts  Costo de salida por línea, en el
     *                                        mismo orden en que llegan. Lo
     *                                        resuelve `TransferCostService`.
     */
    public function create(CreateTransferCommand $command, array $unitCosts): void;

    public function findById(string $id, ?string $companyId = null): ?Transfer;

    public function findOrFail(string $id, ?string $companyId = null): Transfer;

    /**
     * @param  array<int, float>  $unitCosts
     */
    public function update(Transfer $model, UpdateTransferCommand $command, array $unitCosts): void;

    public function updateStatus(Transfer $model, UpdateStatusTransferCommand $command): void;

    /** Marca quién despachó desde el origen. Solo lo llama el posting. */
    public function writeShipment(Transfer $model, ?string $sentBy): Transfer;

    /**
     * Escribe la llegada ya resuelta. Solo lo llama `TransferReceiptService`.
     */
    public function writeReceipt(Transfer $model, WriteTransferReceiptCommand $command): Transfer;

    /**
     * Escribe en la línea el costo con el que la mercancía salió y la cantidad
     * que salió de verdad. Solo lo llama `TransferPostingService`, con lo que el
     * kardex acaba de valorar.
     */
    public function writeLineCost(TransferLine $line, WriteTransferLineCostCommand $command): TransferLine;

    /** Recalcula los totales de la cabecera a partir de sus líneas activas. */
    public function refreshTotals(Transfer $model): Transfer;

    /** @return array{ data: Transfer[], total: int } */
    public function search(SearchTransferCommand $command): array;

    /**
     * Líneas activas del traslado, con lo que el kardex necesita para mover la
     * mercancía.
     *
     * @return array<int, TransferLine>
     */
    public function activeLines(Transfer $model): array;
}
