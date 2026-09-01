<?php

declare(strict_types=1);

namespace App\Modules\ClientCollection\Repositories\Contracts;

use App\Modules\ClientCollection\Commands\CreateClientCollectionCommand;
use App\Modules\ClientCollection\Commands\PostClientCollectionApplicationCommand;
use App\Modules\ClientCollection\Commands\SearchClientCollectionCommand;
use App\Modules\ClientCollection\Commands\UpdateCheckStatusClientCollectionCommand;
use App\Modules\ClientCollection\Commands\UpdateClientCollectionCommand;
use App\Modules\ClientCollection\Commands\UpdateStatusClientCollectionCommand;
use App\Modules\ClientCollection\Models\ClientCollection;
use App\Modules\ClientCollection\Models\ClientCollectionApplication;
use App\Modules\ExchangeRate\Commands\DocumentRatesData;

interface ClientCollectionRepositoryInterface
{
    public function create(CreateClientCollectionCommand $command, DocumentRatesData $rates): void;

    public function findById(string $id, ?string $companyId = null): ?ClientCollection;

    public function findOrFail(string $id, ?string $companyId = null): ClientCollection;

    public function update(ClientCollection $model, UpdateClientCollectionCommand $command, DocumentRatesData $rates): void;

    public function updateStatus(ClientCollection $model, UpdateStatusClientCollectionCommand $command): void;

    /** Mueve el cheque por su carril sin tocar los importes del cobro. */
    public function updateCheckStatus(
        ClientCollection $model,
        UpdateCheckStatusClientCollectionCommand $command,
    ): void;

    /**
     * El reparto vigente del cobro, en el orden en que se capturó.
     *
     * @return array<int, ClientCollectionApplication>
     */
    public function activeApplications(ClientCollection $model): array;

    /** Deja la aplicación abonada: es lo que hace el cobro al confirmarse. */
    public function postApplication(
        ClientCollectionApplication $application,
        PostClientCollectionApplicationCommand $command,
    ): void;

    /** Revertir no borra la fila: la deja en `reversed` y libera el saldo. */
    public function reverseApplication(ClientCollectionApplication $application): void;

    /** @return array{ data: ClientCollection[], total: int } */
    public function search(SearchClientCollectionCommand $command): array;
}
