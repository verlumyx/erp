<?php

declare(strict_types=1);

namespace App\Modules\SalesReturn\Repositories\Contracts;

use App\Modules\ExchangeRate\Commands\DocumentRatesData;
use App\Modules\SalesReturn\Commands\CreateSalesReturnCommand;
use App\Modules\SalesReturn\Commands\SearchSalesReturnCommand;
use App\Modules\SalesReturn\Commands\UpdateSalesReturnCommand;
use App\Modules\SalesReturn\Commands\UpdateStatusSalesReturnCommand;
use App\Modules\SalesReturn\Commands\WriteSalesReturnCreditNoteCommand;
use App\Modules\SalesReturn\Models\SalesReturn;
use App\Modules\SalesReturn\Models\SalesReturnLine;

interface SalesReturnRepositoryInterface
{
    /**
     * @param  array<int, float>  $unitCosts  Costo de reingreso por línea, en el
     *                                        mismo orden en que llegan. Lo
     *                                        resuelve `SalesReturnCostService`.
     */
    public function create(CreateSalesReturnCommand $command, DocumentRatesData $rates, array $unitCosts): void;

    public function findById(string $id, ?string $companyId = null): ?SalesReturn;

    public function findOrFail(string $id, ?string $companyId = null): SalesReturn;

    /**
     * @param  array<int, float>  $unitCosts
     */
    public function update(
        SalesReturn $model,
        UpdateSalesReturnCommand $command,
        DocumentRatesData $rates,
        array $unitCosts,
    ): void;

    public function updateStatus(SalesReturn $model, UpdateStatusSalesReturnCommand $command): void;

    /**
     * La devolución con su fila bloqueada, para que dos notas de crédito que la
     * acreditan a la vez no la lean sin acreditar.
     */
    public function lockById(string $id, ?string $companyId = null): ?SalesReturn;

    /**
     * Escribe el vínculo con la nota de crédito ya resuelto. Solo lo llama
     * `SalesReturnApplyCreditNoteService`.
     */
    public function writeCreditNote(
        SalesReturn $model,
        WriteSalesReturnCreditNoteCommand $command,
    ): SalesReturn;

    /** @return array{ data: SalesReturn[], total: int } */
    public function search(SearchSalesReturnCommand $command): array;

    /**
     * Líneas activas de la devolución, con lo que el kardex necesita para
     * valorar la entrada: el artículo y la línea de factura que la origina.
     *
     * @return array<int, SalesReturnLine>
     */
    public function activeLines(SalesReturn $model): array;

    /**
     * Cantidad ya devuelta de cada línea de factura por devoluciones distintas
     * de la indicada. Es lo que limita cuánto puede devolver una línea nueva.
     *
     * @param  array<int, string>  $invoiceLineIds
     * @return array<string, float> Id de la línea de factura → cantidad devuelta.
     */
    public function returnedQuantities(array $invoiceLineIds, ?string $exceptReturnId = null): array;
}
