<?php

declare(strict_types=1);

namespace App\Modules\PriceList\Services;

use App\Modules\PriceList\Commands\SearchPriceListCommand;
use App\Modules\PriceList\Repositories\Contracts\PriceListRepositoryInterface;

class PriceListSearchService
{
    public function __construct(
        private readonly PriceListRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ data: mixed[], total: int }
     */
    public function execute(SearchPriceListCommand $command): array
    {
        return $this->repository->search($command);
    }
}
