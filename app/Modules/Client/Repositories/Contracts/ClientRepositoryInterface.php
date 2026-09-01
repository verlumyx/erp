<?php

declare(strict_types=1);

namespace App\Modules\Client\Repositories\Contracts;

use App\Modules\Client\Commands\CreateClientCommand;
use App\Modules\Client\Commands\SearchClientCommand;
use App\Modules\Client\Commands\UpdateClientCommand;
use App\Modules\Client\Commands\UpdateStatusClientCommand;
use App\Modules\Client\Commands\WriteClientBalancesCommand;
use App\Modules\Client\Models\Client;

interface ClientRepositoryInterface
{
    public function create(CreateClientCommand $command): void;

    public function findById(string $id, ?string $companyId = null): ?Client;

    public function findOrFail(string $id, ?string $companyId = null): Client;

    public function update(Client $model, UpdateClientCommand $command): void;

    public function updateStatus(Client $model, UpdateStatusClientCommand $command): void;

    /**
     * El cliente con su fila bloqueada, para que dos documentos que mueven sus
     * saldos a la vez no lean el mismo número.
     */
    public function lockById(string $id, ?string $companyId = null): ?Client;

    /** Escribe los saldos resueltos. Solo lo llama `ClientApplyBalanceService`. */
    public function writeBalances(Client $model, WriteClientBalancesCommand $command): Client;

    /** @return array{ data: Client[], total: int } */
    public function search(SearchClientCommand $command): array;
}
