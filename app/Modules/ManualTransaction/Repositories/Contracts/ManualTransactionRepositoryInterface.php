<?php

declare(strict_types=1);

namespace App\Modules\ManualTransaction\Repositories\Contracts;

use App\Modules\ManualTransaction\Commands\CreateManualTransactionCommand;
use App\Modules\ManualTransaction\Commands\SearchManualTransactionCommand;
use App\Modules\ManualTransaction\Models\ManualTransaction;

interface ManualTransactionRepositoryInterface
{
    public function create(CreateManualTransactionCommand $command): void;

    public function approve(ManualTransaction $model): void;

    public function cancel(ManualTransaction $model): void;

    public function findById(string $id, ?string $companyId = null): ?ManualTransaction;

    public function findOrFail(string $id, ?string $companyId = null): ManualTransaction;

    /** @return array{ data: ManualTransaction[], total: int } */
    public function search(SearchManualTransactionCommand $command): array;
}
