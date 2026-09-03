<?php

declare(strict_types=1);

namespace App\Modules\Store\Services;

use App\Modules\Client\Models\Client;
use App\Modules\Store\Commands\RegisterStoreCustomerCommand;
use App\Modules\Store\Models\StoreCustomer;
use App\Modules\Store\Repositories\Contracts\StoreCustomerRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Registro desde la tienda. No crea un cliente en el ERP: el cliente nace al
 * convertir el primer pedido. Lo único automático es el vínculo por RIF, que
 * es seguro porque el RIF es único por empresa.
 */
class StoreCustomerRegisterService
{
    public function __construct(
        private readonly StoreCustomerRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ customer: StoreCustomer, token: string }
     *
     * @throws ValidationException
     */
    public function execute(RegisterStoreCustomerCommand $command): array
    {
        $this->guardUnique($command);

        $client = $this->clientByDocument($command);

        $customer = DB::transaction(function () use ($command, $client): StoreCustomer {
            $this->repository->create($command);

            $customer = $this->repository->findOrFail($command->id, $command->companyId);

            if ($client instanceof Client) {
                $this->repository->writeLink($customer, $client->id, null, 'rif');
            }

            $this->repository->touchLastLogin($customer);

            return $this->repository->findOrFail($command->id, $command->companyId);
        });

        return [
            'customer' => $customer,
            'token' => $customer->createToken('store', [StoreCustomer::TOKEN_ABILITY])->plainTextToken,
        ];
    }

    /**
     * @throws ValidationException
     */
    private function guardUnique(RegisterStoreCustomerCommand $command): void
    {
        if ($this->repository->findByEmail($command->companyId, $command->email) !== null) {
            throw ValidationException::withMessages([
                'email' => 'Ya existe una cuenta con este correo.',
            ]);
        }

        if ($command->documentType === null || $command->documentNumber === null) {
            return;
        }

        if ($this->repository->findByDocument($command->companyId, $command->documentType, $command->documentNumber) !== null) {
            throw ValidationException::withMessages([
                'document_number' => 'Ya existe una cuenta con este RIF.',
            ]);
        }
    }

    /**
     * El cliente del ERP con el mismo RIF, si existe y todavía no tiene
     * comprador. Con un comprador ya vinculado no se roba el vínculo: la
     * cuenta nace sin vincular y alguien del ERP decide.
     */
    private function clientByDocument(RegisterStoreCustomerCommand $command): ?Client
    {
        if ($command->documentType === null || $command->documentNumber === null) {
            return null;
        }

        $client = $this->repository->findClientByDocument($command->companyId, $command->documentType, $command->documentNumber);

        if (! $client instanceof Client || $this->repository->findByClient($client->id) !== null) {
            return null;
        }

        return $client;
    }
}
