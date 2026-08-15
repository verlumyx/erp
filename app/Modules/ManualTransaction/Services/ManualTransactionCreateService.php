<?php

declare(strict_types=1);

namespace App\Modules\ManualTransaction\Services;

use App\Modules\ManualTransaction\Commands\CreateManualTransactionCommand;
use App\Modules\ManualTransaction\Models\ManualTransaction;
use App\Modules\ManualTransaction\Repositories\Contracts\ManualTransactionRepositoryInterface;

class ManualTransactionCreateService
{
    public function __construct(
        private readonly ManualTransactionRepositoryInterface $repository,
    ) {}

    public function execute(CreateManualTransactionCommand $command): ManualTransaction
    {
        $this->repository->create($command);

        return $this->repository->findOrFail($command->id, $command->companyId);
    }
}
