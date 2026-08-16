<?php

declare(strict_types=1);

namespace App\Modules\ExchangeRate\Services;

use App\Modules\ExchangeRate\Commands\UpdateStatusExchangeRateCommand;
use App\Modules\ExchangeRate\Exceptions\ExchangeRateNotFoundException;
use App\Modules\ExchangeRate\Models\ExchangeRate;
use App\Modules\ExchangeRate\Repositories\Contracts\ExchangeRateRepositoryInterface;

class ExchangeRateUpdateStatusService
{
    public function __construct(
        private readonly ExchangeRateRepositoryInterface $repository,
    ) {}

    public function execute(string $id, UpdateStatusExchangeRateCommand $command, ?string $companyId = null): ExchangeRate
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new ExchangeRateNotFoundException;
        }

        $this->repository->updateStatus($model, $command);

        return $this->repository->findOrFail($id, $companyId);
    }
}
