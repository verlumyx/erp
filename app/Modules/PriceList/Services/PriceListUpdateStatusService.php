<?php

declare(strict_types=1);

namespace App\Modules\PriceList\Services;

use App\Modules\PriceList\Commands\UpdateStatusPriceListCommand;
use App\Modules\PriceList\Exceptions\PriceListNotFoundException;
use App\Modules\PriceList\Models\PriceList;
use App\Modules\PriceList\Repositories\Contracts\PriceListRepositoryInterface;

class PriceListUpdateStatusService
{
    public function __construct(
        private readonly PriceListRepositoryInterface $repository,
    ) {}

    public function execute(string $id, UpdateStatusPriceListCommand $command, ?string $companyId = null): PriceList
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new PriceListNotFoundException;
        }

        $this->repository->updateStatus($model, $command);

        return $this->repository->findOrFail($id, $companyId);
    }
}
