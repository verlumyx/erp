<?php

declare(strict_types=1);

namespace App\Modules\ExchangeRate\Services;

use App\Modules\ExchangeRate\Commands\CreateExchangeRateCommand;
use App\Modules\ExchangeRate\Exceptions\ExchangeRateNotFoundException;
use App\Modules\ExchangeRate\Models\ExchangeRate;
use App\Modules\ExchangeRate\Repositories\Contracts\ExchangeRateRepositoryInterface;

class ExchangeRateCreateService
{
    public function __construct(
        private readonly ExchangeRateRepositoryInterface $repository,
    ) {}

    /**
     * The persisted record is resolved by its natural key, not by the command id:
     * reloading an existing currency + date + type updates that record instead of
     * creating a new one.
     */
    public function execute(CreateExchangeRateCommand $command): ExchangeRate
    {
        $this->repository->create($command);

        $model = $this->repository->findByRateKey(
            $command->companyId,
            $command->currency,
            $command->rateDate,
            $command->type,
        );

        if ($model === null) {
            throw new ExchangeRateNotFoundException;
        }

        return $model;
    }
}
