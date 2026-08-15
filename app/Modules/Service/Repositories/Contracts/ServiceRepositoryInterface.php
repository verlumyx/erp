<?php

declare(strict_types=1);

namespace App\Modules\Service\Repositories\Contracts;

use App\Modules\Service\Commands\CreateServiceCommand;
use App\Modules\Service\Commands\SearchServiceCommand;
use App\Modules\Service\Commands\UpdateServiceCommand;
use App\Modules\Service\Commands\UpdateStatusServiceCommand;
use App\Modules\Service\Models\Service;

interface ServiceRepositoryInterface
{
    public function create(CreateServiceCommand $command): void;

    public function findById(string $id, ?string $companyId = null): ?Service;

    public function findOrFail(string $id, ?string $companyId = null): Service;

    public function update(Service $model, UpdateServiceCommand $command): void;

    public function updateStatus(Service $model, UpdateStatusServiceCommand $command): void;

    /** @return array{ data: Service[], total: int } */
    public function search(SearchServiceCommand $command): array;
}
