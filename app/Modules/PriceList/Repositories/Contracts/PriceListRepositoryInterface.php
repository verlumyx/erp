<?php

declare(strict_types=1);

namespace App\Modules\PriceList\Repositories\Contracts;

use App\Modules\PriceList\Commands\CreatePriceListCommand;
use App\Modules\PriceList\Commands\SearchPriceListCommand;
use App\Modules\PriceList\Commands\UpdatePriceListCommand;
use App\Modules\PriceList\Commands\UpdateStatusPriceListCommand;
use App\Modules\PriceList\Models\PriceList;

interface PriceListRepositoryInterface
{
    public function create(CreatePriceListCommand $command): void;

    public function findById(string $id, ?string $companyId = null): ?PriceList;

    public function findOrFail(string $id, ?string $companyId = null): PriceList;

    public function update(PriceList $model, UpdatePriceListCommand $command): void;

    public function updateStatus(PriceList $model, UpdateStatusPriceListCommand $command): void;

    /** @return array{ data: PriceList[], total: int } */
    public function search(SearchPriceListCommand $command): array;
}
