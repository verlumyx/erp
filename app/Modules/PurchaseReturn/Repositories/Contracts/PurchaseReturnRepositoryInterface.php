<?php

declare(strict_types=1);

namespace App\Modules\PurchaseReturn\Repositories\Contracts;

use App\Modules\ExchangeRate\Commands\DocumentRatesData;
use App\Modules\PurchaseReturn\Commands\CreatePurchaseReturnCommand;
use App\Modules\PurchaseReturn\Commands\SearchPurchaseReturnCommand;
use App\Modules\PurchaseReturn\Commands\UpdatePurchaseReturnCommand;
use App\Modules\PurchaseReturn\Commands\UpdateStatusPurchaseReturnCommand;
use App\Modules\PurchaseReturn\Commands\WritePurchaseReturnCreditNoteCommand;
use App\Modules\PurchaseReturn\Models\PurchaseReturn;
use App\Modules\PurchaseReturn\Models\PurchaseReturnLine;

interface PurchaseReturnRepositoryInterface
{
    public function create(CreatePurchaseReturnCommand $command, DocumentRatesData $rates): void;

    public function findById(string $id, ?string $companyId = null): ?PurchaseReturn;

    public function findOrFail(string $id, ?string $companyId = null): PurchaseReturn;

    public function update(
        PurchaseReturn $model,
        UpdatePurchaseReturnCommand $command,
        DocumentRatesData $rates,
    ): void;

    public function updateStatus(PurchaseReturn $model, UpdateStatusPurchaseReturnCommand $command): void;

    /**
     * La devolución con su fila bloqueada, para que dos notas de crédito que la
     * acreditan a la vez no la lean sin acreditar.
     */
    public function lockById(string $id, ?string $companyId = null): ?PurchaseReturn;

    /**
     * Escribe el vínculo con la nota de crédito ya resuelto. Solo lo llama
     * `PurchaseReturnApplyCreditNoteService`.
     */
    public function writeCreditNote(
        PurchaseReturn $model,
        WritePurchaseReturnCreditNoteCommand $command,
    ): PurchaseReturn;

    /** @return array{ data: PurchaseReturn[], total: int } */
    public function search(SearchPurchaseReturnCommand $command): array;

    /**
     * Líneas activas de la devolución, con lo que hace falta para consumir el
     * cupo: el artículo y la línea de factura que la origina.
     *
     * @return array<int, PurchaseReturnLine>
     */
    public function activeLines(PurchaseReturn $model): array;

    /**
     * Cantidad ya devuelta de cada línea de factura por devoluciones distintas
     * de la indicada. Es lo que limita cuánto puede devolver una línea nueva.
     *
     * @param  array<int, string>  $invoiceLineIds
     * @return array<string, float> Id de la línea de factura → cantidad devuelta.
     */
    public function returnedQuantities(array $invoiceLineIds, ?string $exceptReturnId = null): array;
}
