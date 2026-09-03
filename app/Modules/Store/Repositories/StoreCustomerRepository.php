<?php

declare(strict_types=1);

namespace App\Modules\Store\Repositories;

use App\Modules\Client\Models\Client;
use App\Modules\Store\Commands\RegisterStoreCustomerCommand;
use App\Modules\Store\Commands\SearchStoreCustomerCommand;
use App\Modules\Store\Commands\UpdateStatusStoreCustomerCommand;
use App\Modules\Store\Models\StoreCustomer;
use App\Modules\Store\Repositories\Contracts\StoreCustomerRepositoryInterface;
use Illuminate\Support\Facades\DB;

class StoreCustomerRepository extends StoreCustomerFilters implements StoreCustomerRepositoryInterface
{
    private const DETAIL_RELATIONS = ['client', 'linker'];

    public function create(RegisterStoreCustomerCommand $command): void
    {
        DB::transaction(function () use ($command): void {
            StoreCustomer::create([
                'id' => $command->id,
                'company_id' => $command->companyId,
                'code' => $this->generateNextCode($command->companyId),
                'client_id' => $command->clientId,
                'name' => $command->name,
                'email' => $command->email,
                'phone' => $command->phone,
                'document_type' => $command->documentType,
                'document_number' => $command->documentNumber,
                'password_hash' => $command->passwordHash,
                'linked_at' => $command->clientId === null ? null : now(),
                'linked_by' => $command->clientId === null ? null : $command->linkedBy,
                'link_source' => $command->clientId === null ? null : $command->linkSource,
                'invitation_token_hash' => $command->invitationTokenHash,
                'invitation_expires_at' => $command->invitationExpiresAt,
                'status' => $command->status,
                'created_by' => $command->createdBy,
            ]);
        });
    }

    public function findById(string $id, ?string $companyId = null): ?StoreCustomer
    {
        return StoreCustomer::query()
            ->with(self::DETAIL_RELATIONS)
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->find($id);
    }

    public function findOrFail(string $id, ?string $companyId = null): StoreCustomer
    {
        return StoreCustomer::query()
            ->with(self::DETAIL_RELATIONS)
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->findOrFail($id);
    }

    public function findByEmail(string $companyId, string $email): ?StoreCustomer
    {
        return StoreCustomer::query()
            ->with(self::DETAIL_RELATIONS)
            ->where('company_id', $companyId)
            ->whereRaw('LOWER(email) = ?', [strtolower($email)])
            ->first();
    }

    public function findByDocument(string $companyId, string $documentType, string $documentNumber): ?StoreCustomer
    {
        return StoreCustomer::query()
            ->where('company_id', $companyId)
            ->where('document_type', $documentType)
            ->where('document_number', $documentNumber)
            ->first();
    }

    public function findByClient(string $clientId): ?StoreCustomer
    {
        return StoreCustomer::query()
            ->with(self::DETAIL_RELATIONS)
            ->where('client_id', $clientId)
            ->first();
    }

    public function findByInvitationTokenHash(string $hash): ?StoreCustomer
    {
        return StoreCustomer::query()
            ->with(self::DETAIL_RELATIONS)
            ->where('invitation_token_hash', $hash)
            ->first();
    }

    /**
     * @return array{ data: StoreCustomer[], total: int }
     */
    public function search(SearchStoreCustomerCommand $command): array
    {
        $query = StoreCustomer::query()
            ->with('client')
            ->when($command->companyId, fn ($q) => $q->where('company_id', $command->companyId));

        $query = $this->apply($query, $command->filters);

        $total = $query->count();

        $data = $query->orderByDesc('created_at')
            ->limit($command->limit)
            ->offset($command->offset)
            ->get();

        return ['data' => $data->all(), 'total' => $total];
    }

    public function writeLink(StoreCustomer $model, string $clientId, ?string $linkedBy, string $source): void
    {
        $model->update([
            'client_id' => $clientId,
            'linked_at' => now(),
            'linked_by' => $linkedBy,
            'link_source' => $source,
        ]);
    }

    public function writeDocument(StoreCustomer $model, string $documentType, string $documentNumber): void
    {
        $model->update([
            'document_type' => $documentType,
            'document_number' => $documentNumber,
        ]);
    }

    public function writeInvitation(StoreCustomer $model, string $tokenHash, string $expiresAt): void
    {
        $model->update([
            'invitation_token_hash' => $tokenHash,
            'invitation_expires_at' => $expiresAt,
        ]);
    }

    public function acceptInvitation(StoreCustomer $model, string $passwordHash): void
    {
        $model->update([
            'password_hash' => $passwordHash,
            'email_verified_at' => now(),
            'invitation_token_hash' => null,
            'invitation_expires_at' => null,
            'status' => 'active',
        ]);
    }

    public function touchLastLogin(StoreCustomer $model): void
    {
        $model->update(['last_login_at' => now()]);
    }

    public function updateStatus(StoreCustomer $model, UpdateStatusStoreCustomerCommand $command): void
    {
        $model->update(['status' => $command->status]);
    }

    public function findClientById(string $id, string $companyId): ?Client
    {
        return Client::query()
            ->where('company_id', $companyId)
            ->find($id);
    }

    public function findClientByDocument(string $companyId, string $documentType, string $documentNumber): ?Client
    {
        return Client::query()
            ->where('company_id', $companyId)
            ->where('document_type', $documentType)
            ->where('document_number', $documentNumber)
            ->first();
    }

    public function findClientByEmail(string $companyId, string $email): ?Client
    {
        $matches = Client::query()
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->whereRaw('LOWER(email) = ?', [strtolower($email)])
            ->limit(2)
            ->get();

        return $matches->count() === 1 ? $matches->first() : null;
    }

    /**
     * Siguiente código secuencial por empresa (CWE000001, CWE000002, …).
     */
    private function generateNextCode(string $companyId): string
    {
        $last = StoreCustomer::query()
            ->where('company_id', $companyId)
            ->where('code', 'like', StoreCustomer::CODE_PREFIX.'%')
            ->lockForUpdate()
            ->orderByDesc('code')
            ->value('code');

        $next = $last !== null
            ? ((int) substr($last, strlen(StoreCustomer::CODE_PREFIX))) + 1
            : 1;

        return StoreCustomer::CODE_PREFIX.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
