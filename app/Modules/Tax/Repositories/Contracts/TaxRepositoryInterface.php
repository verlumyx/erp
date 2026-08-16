<?php

declare(strict_types=1);

namespace App\Modules\Tax\Repositories\Contracts;

use App\Modules\Tax\Commands\CreateTaxCommand;
use App\Modules\Tax\Commands\SearchTaxCommand;
use App\Modules\Tax\Commands\UpdateStatusTaxCommand;
use App\Modules\Tax\Commands\UpdateTaxCommand;
use App\Modules\Tax\Models\Tax;

interface TaxRepositoryInterface
{
    public function create(CreateTaxCommand $command): void;

    public function findById(string $id, ?string $companyId = null): ?Tax;

    public function findOrFail(string $id, ?string $companyId = null): Tax;

    public function update(Tax $model, UpdateTaxCommand $command): void;

    public function updateStatus(Tax $model, UpdateStatusTaxCommand $command): void;

    /** @return array{ data: Tax[], total: int } */
    public function search(SearchTaxCommand $command): array;
}
