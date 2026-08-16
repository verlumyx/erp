<?php

declare(strict_types=1);

namespace App\Modules\PriceList\Services;

use App\Modules\PriceList\Commands\CreatePriceListCommand;
use App\Modules\PriceList\Models\PriceList;
use App\Modules\PriceList\Repositories\Contracts\PriceListRepositoryInterface;

class PriceListCreateService
{
    public function __construct(
        private readonly PriceListRepositoryInterface $repository,
    ) {}

    public function execute(CreatePriceListCommand $command): PriceList
    {
        $this->repository->create($command);

        return $this->repository->findOrFail($command->id);
    }
}
