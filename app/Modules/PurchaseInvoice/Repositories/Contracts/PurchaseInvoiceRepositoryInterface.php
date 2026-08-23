<?php

declare(strict_types=1);

namespace App\Modules\PurchaseInvoice\Repositories\Contracts;

use App\Modules\ExchangeRate\Commands\DocumentRatesData;
use App\Modules\PurchaseInvoice\Commands\CreatePurchaseInvoiceCommand;
use App\Modules\PurchaseInvoice\Commands\SearchPurchaseInvoiceCommand;
use App\Modules\PurchaseInvoice\Commands\UpdatePurchaseInvoiceCommand;
use App\Modules\PurchaseInvoice\Commands\UpdateStatusPurchaseInvoiceCommand;
use App\Modules\PurchaseInvoice\Commands\WritePurchaseInvoiceLineReturnCommand;
use App\Modules\PurchaseInvoice\Commands\WritePurchaseInvoicePaymentCommand;
use App\Modules\PurchaseInvoice\Models\PurchaseInvoice;
use App\Modules\PurchaseInvoice\Models\PurchaseInvoiceLine;

interface PurchaseInvoiceRepositoryInterface
{
    /**
     * @param  string  $dueDate  Ya resuelto por el Service: viene del payload o
     *                           de los días de crédito del proveedor.
     */
    public function create(CreatePurchaseInvoiceCommand $command, DocumentRatesData $rates, string $dueDate): void;

    public function findById(string $id, ?string $companyId = null): ?PurchaseInvoice;

    public function findOrFail(string $id, ?string $companyId = null): PurchaseInvoice;

    public function update(
        PurchaseInvoice $model,
        UpdatePurchaseInvoiceCommand $command,
        DocumentRatesData $rates,
        string $dueDate,
    ): void;

    public function updateStatus(PurchaseInvoice $model, UpdateStatusPurchaseInvoiceCommand $command): void;

    /**
     * La factura con su fila bloqueada, para que dos documentos que la abonan
     * a la vez no lean el mismo saldo.
     */
    public function lockById(string $id, ?string $companyId = null): ?PurchaseInvoice;

    /** Escribe el saldo resuelto. Solo lo llama `PurchaseInvoiceApplyPaymentService`. */
    public function writePayment(PurchaseInvoice $model, WritePurchaseInvoicePaymentCommand $command): PurchaseInvoice;

    /**
     * La línea con su fila bloqueada, para que dos devoluciones que la agotan a
     * la vez no lean el mismo cupo.
     */
    public function lockLineById(string $id, ?string $companyId = null): ?PurchaseInvoiceLine;

    /** Escribe lo devuelto. Solo lo llama `PurchaseInvoiceApplyReturnService`. */
    public function writeLineReturn(
        PurchaseInvoiceLine $line,
        WritePurchaseInvoiceLineReturnCommand $command,
    ): PurchaseInvoiceLine;

    /** @return array{ data: PurchaseInvoice[], total: int } */
    public function search(SearchPurchaseInvoiceCommand $command): array;
}
