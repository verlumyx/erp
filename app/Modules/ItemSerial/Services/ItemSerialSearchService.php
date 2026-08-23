<?php

declare(strict_types=1);

namespace App\Modules\ItemSerial\Services;

use App\Modules\ItemSerial\Commands\SearchItemSerialCommand;
use App\Modules\ItemSerial\Repositories\Contracts\ItemSerialRepositoryInterface;

class ItemSerialSearchService
{
    public function __construct(
        private readonly ItemSerialRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ data: mixed[], total: int }
     */
    public function execute(SearchItemSerialCommand $command): array
    {
        return $this->repository->search($command);
    }
}
