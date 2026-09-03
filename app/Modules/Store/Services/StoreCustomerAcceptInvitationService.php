<?php

declare(strict_types=1);

namespace App\Modules\Store\Services;

use App\Modules\Store\Exceptions\InvalidInvitationException;
use App\Modules\Store\Models\StoreCustomer;
use App\Modules\Store\Repositories\Contracts\StoreCustomerRepositoryInterface;
use Illuminate\Support\Facades\Hash;

class StoreCustomerAcceptInvitationService
{
    public function __construct(
        private readonly StoreCustomerRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ customer: StoreCustomer, token: string }
     *
     * @throws InvalidInvitationException
     */
    public function execute(string $companyId, string $token, string $password): array
    {
        $customer = $this->repository->findByInvitationTokenHash(hash('sha256', $token));

        if (! $customer instanceof StoreCustomer
            || $customer->company_id !== $companyId
            || $customer->status !== 'invited'
            || $customer->invitation_expires_at === null
            || $customer->invitation_expires_at->isPast()) {
            throw InvalidInvitationException::create();
        }

        $this->repository->acceptInvitation($customer, Hash::make($password));
        $this->repository->touchLastLogin($customer);

        $customer = $this->repository->findOrFail($customer->id, $companyId);

        return [
            'customer' => $customer,
            'token' => $customer->createToken('store', [StoreCustomer::TOKEN_ABILITY])->plainTextToken,
        ];
    }
}
