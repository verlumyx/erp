<?php

declare(strict_types=1);

namespace App\Modules\Store\Repositories\Contracts;

use App\Modules\Client\Models\Client;
use App\Modules\Store\Commands\RegisterStoreCustomerCommand;
use App\Modules\Store\Commands\SearchStoreCustomerCommand;
use App\Modules\Store\Commands\UpdateStatusStoreCustomerCommand;
use App\Modules\Store\Models\StoreCustomer;

interface StoreCustomerRepositoryInterface
{
    public function create(RegisterStoreCustomerCommand $command): void;

    public function findById(string $id, ?string $companyId = null): ?StoreCustomer;

    public function findOrFail(string $id, ?string $companyId = null): StoreCustomer;

    public function findByEmail(string $companyId, string $email): ?StoreCustomer;

    public function findByDocument(string $companyId, string $documentType, string $documentNumber): ?StoreCustomer;

    public function findByClient(string $clientId): ?StoreCustomer;

    public function findByInvitationTokenHash(string $hash): ?StoreCustomer;

    /** @return array{ data: StoreCustomer[], total: int } */
    public function search(SearchStoreCustomerCommand $command): array;

    /** Guarda el vínculo con el cliente: una sola vez por comprador. */
    public function writeLink(StoreCustomer $model, string $clientId, ?string $linkedBy, string $source): void;

    public function writeDocument(StoreCustomer $model, string $documentType, string $documentNumber): void;

    /** Nuevo token de invitación; el anterior deja de valer. */
    public function writeInvitation(StoreCustomer $model, string $tokenHash, string $expiresAt): void;

    /** Acepta la invitación: pone contraseña, activa y borra el token. */
    public function acceptInvitation(StoreCustomer $model, string $passwordHash): void;

    public function touchLastLogin(StoreCustomer $model): void;

    public function updateStatus(StoreCustomer $model, UpdateStatusStoreCustomerCommand $command): void;

    /*
     * Los clientes del ERP contra los que se vincula. Viven aquí para que el
     * módulo Client no gane consultas que solo la tienda necesita.
     */

    public function findClientById(string $id, string $companyId): ?Client;

    public function findClientByDocument(string $companyId, string $documentType, string $documentNumber): ?Client;

    /** El correo no es único en clientes: se devuelve solo si hay exactamente uno activo con él. */
    public function findClientByEmail(string $companyId, string $email): ?Client;
}
