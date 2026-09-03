<?php

declare(strict_types=1);

namespace App\Modules\Store\Services;

use App\Modules\Store\Models\StoreCustomer;
use App\Modules\Store\Repositories\Contracts\StoreCustomerRepositoryInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class StoreCustomerLoginService
{
    public function __construct(
        private readonly StoreCustomerRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ customer: StoreCustomer, token: string }
     *
     * @throws ValidationException credenciales inválidas (422)
     * @throws AuthorizationException cuenta bloqueada o sin contraseña (403)
     */
    public function execute(string $companyId, string $email, string $password): array
    {
        $customer = $this->repository->findByEmail($companyId, $email);

        if (! $customer instanceof StoreCustomer) {
            throw $this->invalidCredentials();
        }

        if ($customer->status === 'inactive') {
            throw new AuthorizationException('Tu cuenta está bloqueada. Comunícate con tu proveedor.');
        }

        if ($customer->status === 'invited' || $customer->password_hash === null) {
            throw new AuthorizationException('Tu cuenta todavía no tiene contraseña: acepta la invitación que te enviaron.');
        }

        if (! Hash::check($password, $customer->password_hash)) {
            throw $this->invalidCredentials();
        }

        $this->repository->touchLastLogin($customer);

        return [
            'customer' => $customer,
            'token' => $customer->createToken('store', [StoreCustomer::TOKEN_ABILITY])->plainTextToken,
        ];
    }

    private function invalidCredentials(): ValidationException
    {
        return ValidationException::withMessages([
            'email' => 'Correo o contraseña incorrectos.',
        ]);
    }
}
