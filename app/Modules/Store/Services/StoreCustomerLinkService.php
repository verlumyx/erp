<?php

declare(strict_types=1);

namespace App\Modules\Store\Services;

use App\Modules\Client\Models\Client;
use App\Modules\Store\Commands\LinkStoreCustomerCommand;
use App\Modules\Store\Exceptions\ClientAlreadyLinkedException;
use App\Modules\Store\Exceptions\StoreCustomerAlreadyLinkedException;
use App\Modules\Store\Models\StoreCustomer;
use App\Modules\Store\Repositories\Contracts\StoreCustomerRepositoryInterface;
use Illuminate\Validation\ValidationException;

/**
 * Vínculo manual desde el listado de compradores. Se hace una sola vez: un
 * comprador vinculado no se vuelve a vincular ni se desvincula.
 */
class StoreCustomerLinkService
{
    public function __construct(
        private readonly StoreCustomerRepositoryInterface $repository,
        private readonly StoreCustomerFindService $findService,
    ) {}

    /**
     * @throws ValidationException
     */
    public function execute(LinkStoreCustomerCommand $command): StoreCustomer
    {
        $customer = $this->findService->execute($command->storeCustomerId, $command->companyId);

        if ($customer->isLinked()) {
            throw StoreCustomerAlreadyLinkedException::forCustomer((string) $customer->code);
        }

        $client = $this->repository->findClientById($command->clientId, $command->companyId);

        if (! $client instanceof Client) {
            throw ValidationException::withMessages([
                'client_id' => 'El cliente no existe o no pertenece a esta empresa.',
            ]);
        }

        if ($this->repository->findByClient($client->id) !== null) {
            throw ClientAlreadyLinkedException::forClient($client->name);
        }

        $this->repository->writeLink($customer, $client->id, $command->linkedBy, 'manual');

        return $this->repository->findOrFail($customer->id, $command->companyId);
    }
}
