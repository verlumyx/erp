<?php

declare(strict_types=1);

namespace App\Modules\Supplier\Repositories;

use App\Modules\Supplier\Commands\CreateSupplierCommand;
use App\Modules\Supplier\Commands\SearchSupplierCommand;
use App\Modules\Supplier\Commands\SupplierAddressData;
use App\Modules\Supplier\Commands\SupplierContactData;
use App\Modules\Supplier\Commands\UpdateStatusSupplierCommand;
use App\Modules\Supplier\Commands\UpdateSupplierCommand;
use App\Modules\Supplier\Commands\WriteSupplierBalancesCommand;
use App\Modules\Supplier\Models\Supplier;
use App\Modules\Supplier\Models\SupplierAddress;
use App\Modules\Supplier\Models\SupplierContact;
use App\Modules\Supplier\Repositories\Contracts\SupplierRepositoryInterface;
use Illuminate\Support\Facades\DB;

class SupplierRepository extends SupplierFilters implements SupplierRepositoryInterface
{
    public function create(CreateSupplierCommand $command): void
    {
        DB::transaction(function () use ($command): void {
            $supplier = Supplier::create([
                'id' => $command->id,
                'company_id' => $command->companyId,
                'code' => $this->generateNextCode($command->companyId),
                'supplier_type_id' => $command->supplierTypeId,
                'name' => $command->name,
                'legal_name' => $command->legalName,
                'document_type' => $command->documentType,
                'document_number' => $command->documentNumber,
                'email' => $command->email,
                'phone' => $command->phone,
                'mobile' => $command->mobile,
                'website' => $command->website,
                'address' => $command->address,
                'city' => $command->city,
                'state' => $command->state,
                'country' => $command->country,
                'currency' => $command->currency,
                'payment_term_days' => $command->paymentTermDays,
                'credit_limit' => $command->creditLimit,
                /** Los saldos nacen en cero: solo los mueven los documentos. */
                'current_balance' => 0,
                'advance_balance' => 0,
                'lead_time_days' => $command->leadTimeDays,
                'notes' => $command->notes,
                'status' => 'active',
                'created_by' => $command->createdBy,
            ]);

            $this->syncContacts($supplier, $command->contacts);
            $this->syncAddresses($supplier, $command->addresses);
        });
    }

    public function findById(string $id, ?string $companyId = null): ?Supplier
    {
        return Supplier::query()
            ->with(['contacts', 'addresses', 'supplierType'])
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->find($id);
    }

    public function findOrFail(string $id, ?string $companyId = null): Supplier
    {
        return Supplier::query()
            ->with(['contacts', 'addresses', 'supplierType'])
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->findOrFail($id);
    }

    public function update(Supplier $model, UpdateSupplierCommand $command): void
    {
        DB::transaction(function () use ($model, $command): void {
            /** `current_balance` y `advance_balance` quedan fuera: son derivados. */
            $model->update([
                'supplier_type_id' => $command->supplierTypeId,
                'name' => $command->name,
                'legal_name' => $command->legalName,
                'document_type' => $command->documentType,
                'document_number' => $command->documentNumber,
                'email' => $command->email,
                'phone' => $command->phone,
                'mobile' => $command->mobile,
                'website' => $command->website,
                'address' => $command->address,
                'city' => $command->city,
                'state' => $command->state,
                'country' => $command->country,
                'currency' => $command->currency,
                'payment_term_days' => $command->paymentTermDays,
                'credit_limit' => $command->creditLimit,
                'lead_time_days' => $command->leadTimeDays,
                'notes' => $command->notes,
            ]);

            $this->syncContacts($model, $command->contacts);
            $this->syncAddresses($model, $command->addresses);
        });
    }

    public function updateStatus(Supplier $model, UpdateStatusSupplierCommand $command): void
    {
        $model->update([
            'status' => $command->status,
        ]);
    }

    public function lockById(string $id, ?string $companyId = null): ?Supplier
    {
        return Supplier::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->lockForUpdate()
            ->find($id);
    }

    public function writeBalances(Supplier $model, WriteSupplierBalancesCommand $command): Supplier
    {
        $model->update([
            'current_balance' => $command->currentBalance,
            'advance_balance' => $command->advanceBalance,
        ]);

        return $model;
    }

    /**
     * @return array{ data: Supplier[], total: int }
     */
    public function search(SearchSupplierCommand $command): array
    {
        $query = Supplier::query()
            ->with('supplierType')
            ->when($command->companyId, fn ($q) => $q->where('company_id', $command->companyId));

        $query = $this->apply($query, $command->filters);

        $total = $query->count();

        $data = $query->orderBy('name')
            ->limit($command->limit)
            ->offset($command->offset)
            ->get();

        return ['data' => $data->all(), 'total' => $total];
    }

    /**
     * Alinea `app_supplier_contacts` con lo enviado desde la pantalla del proveedor.
     *
     * Las filas existentes se reconocen por su `id`; las que dejan de venir no se
     * borran, se desactivan (política de no borrado). Un `id` que no pertenece a
     * este proveedor se ignora y la fila entra como nueva.
     *
     * @param  array<int, SupplierContactData>  $contacts
     */
    private function syncContacts(Supplier $supplier, array $contacts): void
    {
        $existing = SupplierContact::query()
            ->where('supplier_id', $supplier->id)
            ->get()
            ->keyBy('id');

        $keep = [];

        foreach ($contacts as $contact) {
            $attributes = [
                'company_id' => $supplier->company_id,
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

            $keep[] = SupplierContact::create([
                ...$attributes,
                'supplier_id' => $supplier->id,
            ])->id;
        }

        $this->deactivateMissing(SupplierContact::class, $supplier->id, $keep);
    }

    /**
     * Alinea `app_supplier_addresses` con lo enviado desde la pantalla del proveedor.
     *
     * @param  array<int, SupplierAddressData>  $addresses
     */
    private function syncAddresses(Supplier $supplier, array $addresses): void
    {
        $existing = SupplierAddress::query()
            ->where('supplier_id', $supplier->id)
            ->get()
            ->keyBy('id');

        $keep = [];

        foreach ($addresses as $address) {
            $attributes = [
                'company_id' => $supplier->company_id,
                'type' => $address->type,
                'address' => $address->address,
                'city' => $address->city,
                'state' => $address->state,
                'country' => $address->country,
                'is_default' => $address->isDefault,
                'status' => $address->status,
            ];

            if ($address->id !== null && $existing->has($address->id)) {
                $existing->get($address->id)->update($attributes);
                $keep[] = $address->id;

                continue;
            }

            $keep[] = SupplierAddress::create([
                ...$attributes,
                'supplier_id' => $supplier->id,
            ])->id;
        }

        $this->deactivateMissing(SupplierAddress::class, $supplier->id, $keep);
    }

    /**
     * Desactiva las filas de detalle que ya no vienen en el formulario.
     * Con `$keep` vacío se desactivan todas: el proveedor se quedó sin ese detalle.
     *
     * @param  class-string<SupplierAddress|SupplierContact>  $model
     * @param  array<int, string>  $keep
     */
    private function deactivateMissing(string $model, string $supplierId, array $keep): void
    {
        $model::query()
            ->where('supplier_id', $supplierId)
            ->when($keep !== [], fn ($q) => $q->whereNotIn('id', $keep))
            ->update(['status' => 'inactive']);
    }

    /**
     * Generate the next sequential per-company code (PRO000001, PRO000002, …).
     */
    private function generateNextCode(string $companyId): string
    {
        $last = Supplier::query()
            ->where('company_id', $companyId)
            ->where('code', 'like', Supplier::CODE_PREFIX.'%')
            ->lockForUpdate()
            ->orderByDesc('code')
            ->value('code');

        $next = $last !== null
            ? ((int) substr($last, strlen(Supplier::CODE_PREFIX))) + 1
            : 1;

        return Supplier::CODE_PREFIX.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
