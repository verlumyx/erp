<?php

declare(strict_types=1);

namespace App\Modules\Store\Services;

use App\Modules\Client\Models\Client;
use App\Modules\Store\Commands\InviteStoreCustomerCommand;
use App\Modules\Store\Commands\RegisterStoreCustomerCommand;
use App\Modules\Store\Exceptions\ClientAlreadyLinkedException;
use App\Modules\Store\Models\StoreCustomer;
use App\Modules\Store\Notifications\StoreCustomerInvitation;
use App\Modules\Store\Repositories\Contracts\StoreCustomerRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * «Invitar a la tienda» desde la pantalla del cliente: crea el comprador ya
 * vinculado, en `invited` y sin contraseña, y le manda el enlace para
 * ponerla. Es la vía para los clientes que ya existen en el ERP.
 */
class StoreCustomerInviteService
{
    private const TOKEN_LENGTH = 40;

    private const EXPIRES_IN_DAYS = 7;

    public function __construct(
        private readonly StoreCustomerRepositoryInterface $repository,
        private readonly StoreSettingFindOrCreateService $settings,
    ) {}

    /**
     * @throws ValidationException
     */
    public function execute(InviteStoreCustomerCommand $command): StoreCustomer
    {
        $settings = $this->settings->execute($command->companyId, $command->invitedBy);

        if ($settings->store_url === null) {
            throw ValidationException::withMessages([
                'store_url' => 'Configura la URL pública de la tienda en Ajustes antes de invitar clientes.',
            ]);
        }

        $client = $this->repository->findClientById($command->clientId, $command->companyId);

        if (! $client instanceof Client) {
            throw ValidationException::withMessages(['client_id' => 'El cliente no existe.']);
        }

        if ($client->email === null || $client->email === '') {
            throw ValidationException::withMessages([
                'email' => 'El cliente no tiene correo: agrégalo antes de invitarlo.',
            ]);
        }

        $token = Str::random(self::TOKEN_LENGTH);
        $tokenHash = hash('sha256', $token);
        $expiresAt = now()->addDays(self::EXPIRES_IN_DAYS)->format('Y-m-d H:i:s');

        $customer = DB::transaction(function () use ($command, $client, $tokenHash, $expiresAt): StoreCustomer {
            $existing = $this->repository->findByClient($client->id);

            if ($existing instanceof StoreCustomer) {
                if ($existing->status !== 'invited') {
                    throw ClientAlreadyLinkedException::forClient($client->name);
                }

                $this->repository->writeInvitation($existing, $tokenHash, $expiresAt);

                return $this->repository->findOrFail($existing->id, $command->companyId);
            }

            $this->guardEmailFree($client, $command->companyId);

            $id = (string) Str::uuid7();

            $this->repository->create(new RegisterStoreCustomerCommand(
                id: $id,
                companyId: $command->companyId,
                name: $client->name,
                email: strtolower(trim((string) $client->email)),
                phone: $client->mobile ?? $client->phone,
                documentType: $client->document_type,
                documentNumber: $client->document_number,
                passwordHash: null,
                status: 'invited',
                clientId: $client->id,
                linkSource: 'invitation',
                linkedBy: $command->invitedBy,
                invitationTokenHash: $tokenHash,
                invitationExpiresAt: $expiresAt,
                createdBy: $command->invitedBy,
            ));

            return $this->repository->findOrFail($id, $command->companyId);
        });

        $customer->notify(new StoreCustomerInvitation(
            storeName: $settings->store_name,
            url: rtrim($settings->store_url, '/').'/invitacion/'.$token,
        ));

        return $customer;
    }

    /**
     * @throws ValidationException
     */
    private function guardEmailFree(Client $client, string $companyId): void
    {
        if ($this->repository->findByEmail($companyId, (string) $client->email) !== null) {
            throw ValidationException::withMessages([
                'email' => 'Ya hay un comprador registrado con el correo del cliente. Vincúlalo desde Compradores.',
            ]);
        }

        if ($client->document_type !== null && $client->document_number !== null
            && $this->repository->findByDocument($companyId, $client->document_type, $client->document_number) !== null) {
            throw ValidationException::withMessages([
                'document_number' => 'Ya hay un comprador registrado con el RIF del cliente. Vincúlalo desde Compradores.',
            ]);
        }
    }
}
