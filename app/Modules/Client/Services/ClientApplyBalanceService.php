<?php

declare(strict_types=1);

namespace App\Modules\Client\Services;

use App\Modules\Client\Commands\ApplyClientBalanceCommand;
use App\Modules\Client\Commands\WriteClientBalancesCommand;
use App\Modules\Client\Exceptions\ClientNotFoundException;
use App\Modules\Client\Models\Client;
use App\Modules\Client\Repositories\Contracts\ClientRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * El camino que mueve `current_balance` y `advance_balance` de un cliente.
 *
 * Lo llaman los cobros, los anticipos y las notas de crédito cuando cancelan
 * deuda, siempre con el signo que corresponda. La transacción es anidable:
 * llamado desde el documento que la mueve se suma a la transacción abierta como
 * savepoint.
 */
class ClientApplyBalanceService
{
    public function __construct(
        private readonly ClientRepositoryInterface $repository,
    ) {}

    public function execute(ApplyClientBalanceCommand $command): Client
    {
        return DB::transaction(function () use ($command): Client {
            $client = $this->repository->lockById($command->clientId, $command->companyId);

            if ($client === null) {
                throw new ClientNotFoundException;
            }

            return $this->repository->writeBalances($client, new WriteClientBalancesCommand(
                currentBalance: round((float) $client->current_balance + $command->currentBalanceDelta, 2),
                advanceBalance: round((float) $client->advance_balance + $command->advanceBalanceDelta, 2),
            ));
        });
    }
}
