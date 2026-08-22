<?php

declare(strict_types=1);

namespace App\Modules\PurchaseOrder\Repositories\Contracts;

use App\Modules\ExchangeRate\Commands\DocumentRatesData;
use App\Modules\PurchaseOrder\Commands\CreatePurchaseOrderCommand;
use App\Modules\PurchaseOrder\Commands\SearchPurchaseOrderCommand;
use App\Modules\PurchaseOrder\Commands\UpdatePurchaseOrderCommand;
use App\Modules\PurchaseOrder\Commands\UpdateStatusPurchaseOrderCommand;
use App\Modules\PurchaseOrder\Models\PurchaseOrder;

interface PurchaseOrderRepositoryInterface
{
    public function create(CreatePurchaseOrderCommand $command, DocumentRatesData $rates): void;

    public function findById(string $id, ?string $companyId = null): ?PurchaseOrder;

    public function findOrFail(string $id, ?string $companyId = null): PurchaseOrder;

    public function update(PurchaseOrder $model, UpdatePurchaseOrderCommand $command, DocumentRatesData $rates): void;

    public function updateStatus(PurchaseOrder $model, UpdateStatusPurchaseOrderCommand $command): void;

    /** @return array{ data: PurchaseOrder[], total: int } */
    public function search(SearchPurchaseOrderCommand $command): array;
}
