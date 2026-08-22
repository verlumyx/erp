<?php

declare(strict_types=1);

namespace App\Modules\SalesInvoice\Repositories\Contracts;

use App\Modules\ExchangeRate\Commands\DocumentRatesData;
use App\Modules\SalesInvoice\Commands\CreateSalesInvoiceCommand;
use App\Modules\SalesInvoice\Commands\SearchSalesInvoiceCommand;
use App\Modules\SalesInvoice\Commands\UpdateSalesInvoiceCommand;
use App\Modules\SalesInvoice\Commands\UpdateStatusSalesInvoiceCommand;
use App\Modules\SalesInvoice\Models\SalesInvoice;

interface SalesInvoiceRepositoryInterface
{
    public function create(CreateSalesInvoiceCommand $command, DocumentRatesData $rates): void;

    public function findById(string $id, ?string $companyId = null): ?SalesInvoice;

    public function findOrFail(string $id, ?string $companyId = null): SalesInvoice;

    public function update(SalesInvoice $model, UpdateSalesInvoiceCommand $command, DocumentRatesData $rates): void;

    public function updateStatus(SalesInvoice $model, UpdateStatusSalesInvoiceCommand $command): void;

    /** @return array{ data: SalesInvoice[], total: int } */
    public function search(SearchSalesInvoiceCommand $command): array;
}
