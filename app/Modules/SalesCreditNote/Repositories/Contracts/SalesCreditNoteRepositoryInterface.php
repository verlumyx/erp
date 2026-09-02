<?php

declare(strict_types=1);

namespace App\Modules\SalesCreditNote\Repositories\Contracts;

use App\Modules\ExchangeRate\Commands\DocumentRatesData;
use App\Modules\SalesCreditNote\Commands\CreateSalesCreditNoteCommand;
use App\Modules\SalesCreditNote\Commands\SearchSalesCreditNoteCommand;
use App\Modules\SalesCreditNote\Commands\UpdateSalesCreditNoteCommand;
use App\Modules\SalesCreditNote\Commands\UpdateStatusSalesCreditNoteCommand;
use App\Modules\SalesCreditNote\Commands\WriteSalesCreditNoteAppliedCommand;
use App\Modules\SalesCreditNote\Models\SalesCreditNote;
use App\Modules\SalesCreditNote\Models\SalesCreditNoteLine;

interface SalesCreditNoteRepositoryInterface
{
    public function create(CreateSalesCreditNoteCommand $command, DocumentRatesData $rates): void;

    public function findById(string $id, ?string $companyId = null): ?SalesCreditNote;

    public function findOrFail(string $id, ?string $companyId = null): SalesCreditNote;

    public function update(
        SalesCreditNote $model,
        UpdateSalesCreditNoteCommand $command,
        DocumentRatesData $rates,
    ): void;

    public function updateStatus(SalesCreditNote $model, UpdateStatusSalesCreditNoteCommand $command): void;

    /**
     * La nota con su fila bloqueada, para que dos documentos que gastan su
     * crédito a la vez no lean el mismo disponible.
     */
    public function lockById(string $id, ?string $companyId = null): ?SalesCreditNote;

    /**
     * Escribe lo aplicado y lo disponible ya resueltos. Solo lo llama
     * `SalesCreditNoteApplicationService`.
     */
    public function writeApplied(
        SalesCreditNote $model,
        WriteSalesCreditNoteAppliedCommand $command,
    ): SalesCreditNote;

    /** @return array{ data: SalesCreditNote[], total: int } */
    public function search(SearchSalesCreditNoteCommand $command): array;

    /**
     * Las líneas vivas de la nota, en orden. Es lo que el asiento en el kardex
     * recorre al confirmarla.
     *
     * @return array<int, SalesCreditNoteLine>
     */
    public function activeLines(SalesCreditNote $note): array;

    /**
     * Cantidad ya acreditada de cada línea de factura por notas distintas de la
     * indicada. Es lo que limita cuánto puede acreditar una línea nueva.
     *
     * @param  array<int, string>  $invoiceLineIds
     * @return array<string, float> Id de la línea de factura → cantidad acreditada.
     */
    public function creditedQuantities(array $invoiceLineIds, ?string $exceptNoteId = null): array;

    /**
     * Importe ya acreditado a una factura por notas vivas distintas de la
     * indicada. Con él se comprueba que la nota no supere lo facturado.
     */
    public function creditedAmount(string $invoiceId, ?string $exceptNoteId = null): float;
}
