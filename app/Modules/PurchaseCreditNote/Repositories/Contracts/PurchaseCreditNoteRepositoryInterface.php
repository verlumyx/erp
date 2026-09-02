<?php

declare(strict_types=1);

namespace App\Modules\PurchaseCreditNote\Repositories\Contracts;

use App\Modules\ExchangeRate\Commands\DocumentRatesData;
use App\Modules\PurchaseCreditNote\Commands\CreatePurchaseCreditNoteCommand;
use App\Modules\PurchaseCreditNote\Commands\SearchPurchaseCreditNoteCommand;
use App\Modules\PurchaseCreditNote\Commands\UpdatePurchaseCreditNoteCommand;
use App\Modules\PurchaseCreditNote\Commands\UpdateStatusPurchaseCreditNoteCommand;
use App\Modules\PurchaseCreditNote\Commands\WritePurchaseCreditNoteAppliedCommand;
use App\Modules\PurchaseCreditNote\Models\PurchaseCreditNote;
use App\Modules\PurchaseCreditNote\Models\PurchaseCreditNoteLine;

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

    /**
     * La nota con su fila bloqueada, para que dos documentos que gastan su
     * crédito a la vez no lean el mismo disponible.
     */
    public function lockById(string $id, ?string $companyId = null): ?PurchaseCreditNote;

    /**
     * Escribe lo aplicado y lo disponible ya resueltos. Solo lo llama
     * `PurchaseCreditNoteApplicationService`.
     */
    public function writeApplied(
        PurchaseCreditNote $model,
        WritePurchaseCreditNoteAppliedCommand $command,
    ): PurchaseCreditNote;

    /**
     * Las líneas vivas de la nota, en el orden en que se capturaron. Son las
     * únicas que salen del kardex.
     *
     * @return array<int, PurchaseCreditNoteLine>
     */
    public function activeLines(PurchaseCreditNote $model): array;

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
