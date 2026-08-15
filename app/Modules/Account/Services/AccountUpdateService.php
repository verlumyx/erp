<?php

declare(strict_types=1);

namespace App\Modules\Account\Services;

use App\Modules\Account\Commands\UpdateAccountCommand;
use App\Modules\Account\Exceptions\AccountNotFoundException;
use App\Modules\Account\Models\Account;
use App\Modules\Account\Models\Profile;
use App\Modules\Account\Repositories\Contracts\AccountRepositoryInterface;
use Illuminate\Validation\ValidationException;

class AccountUpdateService
{
    public function __construct(
        private readonly AccountRepositoryInterface $repository,
    ) {}

    public function execute(string $id, UpdateAccountCommand $command, ?string $companyId = null): Account
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new AccountNotFoundException;
        }

        $this->assertValidProfileTransitions($model, $command);

        $this->repository->update($model, $command);

        return $this->repository->findOrFail($id, $companyId);
    }

    /**
     * Valida que cada cambio de status de profile respete las transiciones permitidas.
     */
    private function assertValidProfileTransitions(Account $model, UpdateAccountCommand $command): void
    {
        $profilesByNumber = $model->profiles->keyBy('number');

        foreach ($command->profiles as $linea) {
            $nuevoStatus = $linea['status'] ?? null;

            if ($nuevoStatus === null) {
                continue;
            }

            $profile = $profilesByNumber->get($linea['number']);

            if ($profile === null) {
                continue;
            }

            if (! Profile::canTransition($profile->status, $nuevoStatus)) {
                throw ValidationException::withMessages([
                    'profiles' => "Transición de estado no permitida para el perfil {$linea['number']}: {$profile->status} → {$nuevoStatus}.",
                ]);
            }
        }
    }
}
