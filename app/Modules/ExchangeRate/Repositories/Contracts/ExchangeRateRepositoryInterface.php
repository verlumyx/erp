<?php

declare(strict_types=1);

namespace App\Modules\ExchangeRate\Repositories\Contracts;

use App\Modules\ExchangeRate\Commands\CreateExchangeRateCommand;
use App\Modules\ExchangeRate\Commands\SearchExchangeRateCommand;
use App\Modules\ExchangeRate\Commands\UpdateExchangeRateCommand;
use App\Modules\ExchangeRate\Commands\UpdateStatusExchangeRateCommand;
use App\Modules\ExchangeRate\Models\ExchangeRate;

interface ExchangeRateRepositoryInterface
{
    public function create(CreateExchangeRateCommand $command): void;

    public function findById(string $id, ?string $companyId = null): ?ExchangeRate;

    public function findOrFail(string $id, ?string $companyId = null): ExchangeRate;

    /**
     * Find the single rate identified by its natural key: company + currency + date + type.
     */
    public function findByRateKey(string $companyId, string $currency, string $rateDate, string $type): ?ExchangeRate;

    public function update(ExchangeRate $model, UpdateExchangeRateCommand $command): void;

    public function updateStatus(ExchangeRate $model, UpdateStatusExchangeRateCommand $command): void;

    /** @return array{ data: ExchangeRate[], total: int } */
    public function search(SearchExchangeRateCommand $command): array;
}
