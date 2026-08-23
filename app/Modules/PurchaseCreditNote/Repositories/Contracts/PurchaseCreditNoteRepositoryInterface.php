<?php

declare(strict_types=1);

namespace App\Modules\PurchaseCreditNote\Repositories\Contracts;

use App\Modules\ExchangeRate\Commands\DocumentRatesData;
use App\Modules\PurchaseCreditNote\Commands\CreatePurchaseCreditNoteCommand;
use App\Modules\PurchaseCreditNote\Commands\SearchPurchaseCreditNoteCommand;
use App\Modules\PurchaseCreditNote\Commands\UpdatePurchaseCreditNoteCommand;
use App\Modules\PurchaseCreditNote\Commands\UpdateStatusPurchaseCreditNoteCommand;
use App\Modules\PurchaseCreditNote\Models\PurchaseCreditNote;

interface PurchaseCreditNoteRepositoryInterface
{
    public function create(CreatePurchaseCreditNoteCommand $command, DocumentRatesData $rates): void;

    public function findById(string $id, ?string $companyId = null): ?PurchaseCreditNote;

    public function findOrFail(string $id, ?string $companyId = null): PurchaseCreditNote;

    public function update(
        PurchaseCreditNote $model,
        UpdatePurchaseCreditNoteCommand $command,
        DocumentRatesData $rates,
    ): void;

    public function updateStatus(PurchaseCreditNote $model, UpdateStatusPurchaseCreditNoteCommand $command): void;

    /** @return array{ data: PurchaseCreditNote[], total: int } */
    public function search(SearchPurchaseCreditNoteCommand $command): array;

    /**
     * Cantidad ya acreditada de cada línea de factura por notas distintas de la
     * indicada. Es lo que limita cuánto puede acreditar una línea nueva.
     *
     * @param  array<int, string>  $invoiceLineIds
     * @return array<string, float> Id de la línea de factura → cantidad acreditada.
     */
    public function creditedQuantities(array $invoiceLineIds, ?string $exceptNoteId = null): array;
}
