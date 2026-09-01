<?php

declare(strict_types=1);

namespace App\Modules\SalesInvoice\Repositories\Contracts;

use App\Modules\ExchangeRate\Commands\DocumentRatesData;
use App\Modules\SalesInvoice\Commands\CreateSalesInvoiceCommand;
use App\Modules\SalesInvoice\Commands\SearchSalesInvoiceCommand;
use App\Modules\SalesInvoice\Commands\UpdateSalesInvoiceCommand;
use App\Modules\SalesInvoice\Commands\UpdateStatusSalesInvoiceCommand;
use App\Modules\SalesInvoice\Commands\WriteSalesInvoiceCollectionCommand;
use App\Modules\SalesInvoice\Commands\WriteSalesInvoiceLineReturnCommand;
use App\Modules\SalesInvoice\Models\SalesInvoice;
use App\Modules\SalesInvoice\Models\SalesInvoiceLine;

interface SalesInvoiceRepositoryInterface
{
    public function create(CreateSalesInvoiceCommand $command, DocumentRatesData $rates): void;

    public function findById(string $id, ?string $companyId = null): ?SalesInvoice;

    public function findOrFail(string $id, ?string $companyId = null): SalesInvoice;

    public function update(SalesInvoice $model, UpdateSalesInvoiceCommand $command, DocumentRatesData $rates): void;

    public function updateStatus(SalesInvoice $model, UpdateStatusSalesInvoiceCommand $command): void;

    /**
     * La factura con su fila bloqueada, para que dos documentos que abonan lo
     * mismo a la vez no lean el mismo saldo.
     */
    public function lockById(string $id, ?string $companyId = null): ?SalesInvoice;

    /**
     * Escribe lo cobrado ya resuelto. Solo lo llama
     * `SalesInvoiceApplyCollectionService`.
     */
    public function writeCollection(SalesInvoice $model, WriteSalesInvoiceCollectionCommand $command): SalesInvoice;

    /**
     * La línea con su fila bloqueada, para que dos devoluciones que devuelven
     * lo mismo a la vez no lean el mismo cupo.
     */
    public function lockLineById(string $id, ?string $companyId = null): ?SalesInvoiceLine;

    /**
     * Escribe lo devuelto de la línea ya resuelto. Solo lo llama
     * `SalesInvoiceApplyReturnService`.
     */
    public function writeLineReturn(
        SalesInvoiceLine $line,
        WriteSalesInvoiceLineReturnCommand $command,
    ): SalesInvoiceLine;

    /** @return array{ data: SalesInvoice[], total: int } */
    public function search(SearchSalesInvoiceCommand $command): array;
}
