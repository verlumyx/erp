<?php

declare(strict_types=1);

namespace App\Modules\Client\Repositories;

use App\Modules\Client\Commands\ClientAddressData;
use App\Modules\Client\Commands\ClientContactData;
use App\Modules\Client\Commands\CreateClientCommand;
use App\Modules\Client\Commands\SearchClientCommand;
use App\Modules\Client\Commands\UpdateClientCommand;
use App\Modules\Client\Commands\UpdateStatusClientCommand;
use App\Modules\Client\Models\Client;
use App\Modules\Client\Models\ClientAddress;
use App\Modules\Client\Models\ClientContact;
use App\Modules\Client\Repositories\Contracts\ClientRepositoryInterface;
use Illuminate\Support\Facades\DB;

class ClientRepository extends ClientFilters implements ClientRepositoryInterface
{
    public function create(CreateClientCommand $command): void
    {
        DB::transaction(function () use ($command): void {
            $client = Client::create([
                'id' => $command->id,
                'company_id' => $command->companyId,
                'code' => $this->generateNextCode($command->companyId),
                'client_type_id' => $command->clientTypeId,
                'price_list_id' => $command->priceListId,
                'name' => $command->name,
                'legal_name' => $command->legalName,
                'document_type' => $command->documentType,
                'document_number' => $command->documentNumber,
                'phone' => $command->phone,
                'mobile' => $command->mobile,
                'email' => $command->email,
                'address' => $command->address,
                'city' => $command->city,
                'state' => $command->state,
                'country' => $command->country,
                'payment_term_days' => $command->paymentTermDays,
                'credit_limit' => $command->creditLimit,
                'credit_blocked' => $command->creditBlocked,
                /** Los saldos nacen en cero: solo los mueven los documentos. */
                'current_balance' => 0,
                'advance_balance' => 0,
                'discount_percent' => $command->discountPercent,
                'salesperson_id' => $command->salespersonId,
                'latitude' => $command->latitude,
                'longitude' => $command->longitude,
                'notes' => $command->notes,
                'status' => 'active',
                'created_by' => $command->createdBy,
            ]);

            $this->syncContacts($client, $command->contacts);
            $this->syncAddresses($client, $command->addresses);
        });
    }

    public function findById(string $id, ?string $companyId = null): ?Client
    {
        return Client::query()
            ->with(['contacts', 'addresses', 'clientType', 'priceList', 'salesperson'])
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->find($id);
    }

    public function findOrFail(string $id, ?string $companyId = null): Client
    {
        return Client::query()
            ->with(['contacts', 'addresses', 'clientType', 'priceList', 'salesperson'])
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->findOrFail($id);
    }

    public function update(Client $model, UpdateClientCommand $command): void
    {
        DB::transaction(function () use ($model, $command): void {
            /** `current_balance` y `advance_balance` quedan fuera: son derivados. */
            $model->update([
                'client_type_id' => $command->clientTypeId,
                'price_list_id' => $command->priceListId,
                'name' => $command->name,
                'legal_name' => $command->legalName,
                'document_type' => $command->documentType,
                'document_number' => $command->documentNumber,
                'phone' => $command->phone,
                'mobile' => $command->mobile,
                'email' => $command->email,
                'address' => $command->address,
                'city' => $command->city,
                'state' => $command->state,
                'country' => $command->country,
                'payment_term_days' => $command->paymentTermDays,
                'credit_limit' => $command->creditLimit,
                'credit_blocked' => $command->creditBlocked,
                'discount_percent' => $command->discountPercent,
                'salesperson_id' => $command->salespersonId,
                'latitude' => $command->latitude,
                'longitude' => $command->longitude,
                'notes' => $command->notes,
            ]);

            $this->syncContacts($model, $command->contacts);
            $this->syncAddresses($model, $command->addresses);
        });
    }

    public function updateStatus(Client $model, UpdateStatusClientCommand $command): void
    {
        $model->update([
            'status' => $command->status,
        ]);
    }

    /**
     * @return array{ data: Client[], total: int }
     */
    public function search(SearchClientCommand $command): array
    {
        $query = Client::query()
            ->with(['clientType', 'priceList'])
            ->when($command->companyId, fn ($q) => $q->where('company_id', $command->companyId));

        $query = $this->apply($query, $command->filters);

        $total = $query->count();

        $data = $query->orderByDesc('created_at')
            ->limit($command->limit)
            ->offset($command->offset)
            ->get();

        return ['data' => $data->all(), 'total' => $total];
    }

    /**
     * Alinea `app_client_contacts` con lo enviado desde la pantalla del cliente.
     *
     * Las filas existentes se reconocen por su `id`; las que dejan de venir no se
     * borran, se desactivan (política de no borrado). Un `id` que no pertenece a
     * este cliente se ignora y la fila entra como nueva.
     *
     * @param  array<int, ClientContactData>  $contacts
     */
    private function syncContacts(Client $client, array $contacts): void
    {
        $existing = ClientContact::query()
            ->where('client_id', $client->id)
            ->get()
            ->keyBy('id');

        $keep = [];

        foreach ($contacts as $contact) {
            $attributes = [
                'company_id' => $client->company_id,
                'name' => $contact->name,
                'position' => $contact->position,
                'email' => $contact->email,
                'phone' => $contact->phone,
                'is_primary' => $contact->isPrimary,
                'status' => $contact->status,
            ];

            if ($contact->id !== null && $existing->has($contact->id)) {
                $existing->get($contact->id)->update($attributes);
                $keep[] = $contact->id;

                continue;
            }

            $keep[] = ClientContact::create([
                ...$attributes,
                'client_id' => $client->id,
            ])->id;
        }

        $this->deactivateMissing(ClientContact::class, $client->id, $keep);
    }

    /**
     * Alinea `app_client_addresses` con lo enviado desde la pantalla del cliente.
     *
     * @param  array<int, ClientAddressData>  $addresses
     */
    private function syncAddresses(Client $client, array $addresses): void
    {
        $existing = ClientAddress::query()
            ->where('client_id', $client->id)
            ->get()
            ->keyBy('id');

        $keep = [];

        foreach ($addresses as $address) {
            $attributes = [
                'company_id' => $client->company_id,
                'type' => $address->type,
                'name' => $address->name,
                'address' => $address->address,
                'city' => $address->city,
                'state' => $address->state,
                'country' => $address->country,
                'latitude' => $address->latitude,
                'longitude' => $address->longitude,
                'is_default' => $address->isDefault,
                'status' => $address->status,
            ];

            if ($address->id !== null && $existing->has($address->id)) {
                $existing->get($address->id)->update($attributes);
                $keep[] = $address->id;

                continue;
            }

            $keep[] = ClientAddress::create([
                ...$attributes,
                'client_id' => $client->id,
            ])->id;
        }

        $this->deactivateMissing(ClientAddress::class, $client->id, $keep);
    }

    /**
     * Desactiva las filas de detalle que ya no vienen en el formulario.
     * Con `$keep` vacío se desactivan todas: el cliente se quedó sin ese detalle.
     *
     * @param  class-string<ClientAddress|ClientContact>  $model
     * @param  array<int, string>  $keep
     */
    private function deactivateMissing(string $model, string $clientId, array $keep): void
    {
        $model::query()
            ->where('client_id', $clientId)
            ->when($keep !== [], fn ($q) => $q->whereNotIn('id', $keep))
            ->update(['status' => 'inactive']);
    }

    /**
     * Generate the next sequential per-company code (CLI000001, CLI000002, …).
     */
    private function generateNextCode(string $companyId): string
    {
        $last = Client::query()
            ->where('company_id', $companyId)
            ->where('code', 'like', Client::CODE_PREFIX.'%')
            ->lockForUpdate()
            ->orderByDesc('code')
            ->value('code');

        $next = $last !== null
            ? ((int) substr($last, strlen(Client::CODE_PREFIX))) + 1
            : 1;

        return Client::CODE_PREFIX.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
