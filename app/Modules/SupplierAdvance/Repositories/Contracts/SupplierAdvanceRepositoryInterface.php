<?php

declare(strict_types=1);

namespace App\Modules\SupplierAdvance\Repositories\Contracts;

use App\Modules\ExchangeRate\Commands\DocumentRatesData;
use App\Modules\SupplierAdvance\Commands\CreateSupplierAdvanceCommand;
use App\Modules\SupplierAdvance\Commands\SearchSupplierAdvanceCommand;
use App\Modules\SupplierAdvance\Commands\UpdateStatusSupplierAdvanceCommand;
use App\Modules\SupplierAdvance\Commands\UpdateSupplierAdvanceCommand;
use App\Modules\SupplierAdvance\Models\SupplierAdvance;

interface SupplierAdvanceRepositoryInterface
{
    public function create(CreateSupplierAdvanceCommand $command, DocumentRatesData $rates): void;

    public function findById(string $id, ?string $companyId = null): ?SupplierAdvance;

    public function findOrFail(string $id, ?string $companyId = null): SupplierAdvance;

    public function update(SupplierAdvance $model, UpdateSupplierAdvanceCommand $command, DocumentRatesData $rates): void;

    public function updateStatus(SupplierAdvance $model, UpdateStatusSupplierAdvanceCommand $command): void;

    /**
     * El anticipo con su fila bloqueada, para que dos documentos que lo mueven
     * a la vez no lean el mismo saldo.
     */
    public function lockById(string $id, ?string $companyId = null): ?SupplierAdvance;

    /** @return array{ data: SupplierAdvance[], total: int } */
    public function search(SearchSupplierAdvanceCommand $command): array;
}
