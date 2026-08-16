<?php

declare(strict_types=1);

namespace App\Modules\ClientType\Repositories\Contracts;

use App\Modules\ClientType\Commands\CreateClientTypeCommand;
use App\Modules\ClientType\Commands\SearchClientTypeCommand;
use App\Modules\ClientType\Commands\UpdateClientTypeCommand;
use App\Modules\ClientType\Commands\UpdateStatusClientTypeCommand;
use App\Modules\ClientType\Models\ClientType;

interface ClientTypeRepositoryInterface
{
    public function create(CreateClientTypeCommand $command): void;

    public function findById(string $id, ?string $companyId = null): ?ClientType;

    public function findOrFail(string $id, ?string $companyId = null): ClientType;

    public function update(ClientType $model, UpdateClientTypeCommand $command): void;

    public function updateStatus(ClientType $model, UpdateStatusClientTypeCommand $command): void;

    /** @return array{ data: ClientType[], total: int } */
    public function search(SearchClientTypeCommand $command): array;
}
