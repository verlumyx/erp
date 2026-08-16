<?php

declare(strict_types=1);

namespace App\Modules\ExchangeRate\Services;

use App\Modules\ExchangeRate\Exceptions\ExchangeRateNotFoundException;
use App\Modules\ExchangeRate\Models\ExchangeRate;
use App\Modules\ExchangeRate\Repositories\Contracts\ExchangeRateRepositoryInterface;

class ExchangeRateFindService
{
    public function __construct(
        private readonly ExchangeRateRepositoryInterface $repository,
    ) {}

    public function execute(string $id, ?string $companyId = null): ExchangeRate
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new ExchangeRateNotFoundException;
        }

        return $model;
    }
}
