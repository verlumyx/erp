<?php

declare(strict_types=1);

namespace App\Modules\ExchangeRate\Services;

use App\Modules\ExchangeRate\Commands\SearchExchangeRateCommand;
use App\Modules\ExchangeRate\Repositories\Contracts\ExchangeRateRepositoryInterface;

class ExchangeRateSearchService
{
    public function __construct(
        private readonly ExchangeRateRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ data: mixed[], total: int }
     */
    public function execute(SearchExchangeRateCommand $command): array
    {
        return $this->repository->search($command);
    }
}
