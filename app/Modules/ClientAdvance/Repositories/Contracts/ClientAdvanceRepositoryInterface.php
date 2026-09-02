<?php

declare(strict_types=1);

namespace App\Modules\ClientAdvance\Repositories\Contracts;

use App\Modules\ClientAdvance\Commands\CreateClientAdvanceCommand;
use App\Modules\ClientAdvance\Commands\SearchClientAdvanceCommand;
use App\Modules\ClientAdvance\Commands\UpdateClientAdvanceCommand;
use App\Modules\ClientAdvance\Commands\UpdateStatusClientAdvanceCommand;
use App\Modules\ClientAdvance\Commands\WriteClientAdvanceAppliedCommand;
use App\Modules\ClientAdvance\Models\ClientAdvance;
use App\Modules\ExchangeRate\Commands\DocumentRatesData;

interface ClientAdvanceRepositoryInterface
{
    public function create(CreateClientAdvanceCommand $command, DocumentRatesData $rates): void;

    public function findById(string $id, ?string $companyId = null): ?ClientAdvance;

    public function findOrFail(string $id, ?string $companyId = null): ClientAdvance;

    public function update(ClientAdvance $model, UpdateClientAdvanceCommand $command, DocumentRatesData $rates): void;

    public function updateStatus(ClientAdvance $model, UpdateStatusClientAdvanceCommand $command): void;

    /**
     * Escribe lo aplicado y lo disponible ya resueltos. Solo lo llama
     * `ClientCollectionCreditSourceService`.
     */
    public function writeApplied(
        ClientAdvance $model,
        WriteClientAdvanceAppliedCommand $command,
    ): ClientAdvance;

    /**
     * El anticipo con su fila bloqueada, para que dos documentos que lo mueven
     * a la vez no lean el mismo saldo.
     */
    public function lockById(string $id, ?string $companyId = null): ?ClientAdvance;

    /** @return array{ data: ClientAdvance[], total: int } */
    public function search(SearchClientAdvanceCommand $command): array;
}
