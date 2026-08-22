<?php

declare(strict_types=1);

namespace App\Modules\PurchaseInvoice\Repositories\Contracts;

use App\Modules\ExchangeRate\Commands\DocumentRatesData;
use App\Modules\PurchaseInvoice\Commands\CreatePurchaseInvoiceCommand;
use App\Modules\PurchaseInvoice\Commands\SearchPurchaseInvoiceCommand;
use App\Modules\PurchaseInvoice\Commands\UpdatePurchaseInvoiceCommand;
use App\Modules\PurchaseInvoice\Commands\UpdateStatusPurchaseInvoiceCommand;
use App\Modules\PurchaseInvoice\Models\PurchaseInvoice;

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

    /** @return array{ data: PurchaseInvoice[], total: int } */
    public function search(SearchPurchaseInvoiceCommand $command): array;
}
