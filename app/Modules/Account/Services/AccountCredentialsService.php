<?php

declare(strict_types=1);

namespace App\Modules\Account\Services;

use App\Modules\Account\Exceptions\AccountNotFoundException;
use App\Modules\Account\Repositories\Contracts\AccountRepositoryInterface;
use Illuminate\Support\Facades\Log;

class AccountCredentialsService
{
    public function __construct(
        private readonly AccountRepositoryInterface $repository,
    ) {}

    /**
     * Devuelve las credentials descifradas de la account y registra el acceso.
     * El descifrado de `password_encrypted` ocurre vía el cast `encrypted` del modelo.
     *
     * @return array{email: string, password: string}
     */
    public function execute(string $id, string $userId, ?string $companyId = null): array
    {
        $account = $this->repository->findById($id, $companyId);

        if ($account === null) {
            throw new AccountNotFoundException;
        }

        // Hook de auditoría: cada acceso a credentials queda registrado.
        // TODO: reemplazar por un log de auditoría persistente cuando exista el módulo.
        Log::info('account.credentials.accessed', [
            'user_id' => $userId,
            'account_id' => $account->id,
            'company_id' => $account->company_id,
            'code' => $account->code,
        ]);

        return [
            'email' => $account->email,
            'password' => $account->password_encrypted,
        ];
    }
}
