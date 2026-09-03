<?php

declare(strict_types=1);

namespace App\Modules\Store\Services;

use App\Modules\Store\Commands\UpdateStatusStoreCustomerCommand;
use App\Modules\Store\Models\StoreCustomer;
use App\Modules\Store\Repositories\Contracts\StoreCustomerRepositoryInterface;
use Illuminate\Validation\ValidationException;

/**
 * `active` ↔ `inactive` desde el ERP. Un comprador `invited` no se toca aquí:
 * pasa a `active` solo al aceptar la invitación.
 */
class StoreCustomerUpdateStatusService
{
    public function __construct(
        private readonly StoreCustomerRepositoryInterface $repository,
        private readonly StoreCustomerFindService $findService,
    ) {}

    /**
     * @throws ValidationException
     */
    public function execute(string $id, string $companyId, UpdateStatusStoreCustomerCommand $command): StoreCustomer
    {
        $customer = $this->findService->execute($id, $companyId);

        if ($customer->status === 'invited') {
            throw ValidationException::withMessages([
                'status' => 'Un comprador invitado se activa al aceptar la invitación.',
            ]);
        }

        $this->repository->updateStatus($customer, $command);

        if ($command->status === 'inactive') {
            $customer->tokens()->delete();
        }

        return $this->repository->findOrFail($id, $companyId);
    }
}
