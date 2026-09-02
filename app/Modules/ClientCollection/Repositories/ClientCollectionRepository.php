<?php

declare(strict_types=1);

namespace App\Modules\ClientCollection\Repositories;

use App\Modules\ClientCollection\Commands\ClientCollectionApplicationData;
use App\Modules\ClientCollection\Commands\CreateClientCollectionCommand;
use App\Modules\ClientCollection\Commands\PostClientCollectionApplicationCommand;
use App\Modules\ClientCollection\Commands\SearchClientCollectionCommand;
use App\Modules\ClientCollection\Commands\UpdateCheckStatusClientCollectionCommand;
use App\Modules\ClientCollection\Commands\UpdateClientCollectionCommand;
use App\Modules\ClientCollection\Commands\UpdateStatusClientCollectionCommand;
use App\Modules\ClientCollection\Commands\WriteClientCollectionApplicationCommand;
use App\Modules\ClientCollection\Models\ClientCollection;
use App\Modules\ClientCollection\Models\ClientCollectionApplication;
use App\Modules\ClientCollection\Repositories\Contracts\ClientCollectionRepositoryInterface;
use App\Modules\ExchangeRate\Commands\DocumentRatesData;
use Illuminate\Support\Facades\DB;

class ClientCollectionRepository extends ClientCollectionFilters implements ClientCollectionRepositoryInterface
{
    public function create(CreateClientCollectionCommand $command, DocumentRatesData $rates): void
    {
        DB::transaction(function () use ($command, $rates): void {
            $collection = ClientCollection::create([
                'id' => $command->id,
                'company_id' => $command->companyId,
                'code' => $this->generateNextCode($command->companyId),
                'client_id' => $command->clientId,
                'origin_type' => $command->originType,
                'origin_id' => $command->originId,
                'credit_source_id' => $this->creditSource($command->paymentMethod, $command->creditSourceId),
                'collection_date' => $command->collectionDate,
                'payment_method' => $command->paymentMethod,
                'reference' => $command->reference,
                'bank_account' => $command->bankAccount,
                'collected_by' => $command->collectedBy,
                'route_id' => $command->routeId,
                ...$rates->toAttributes(),
                ...$this->totals($command->amount, $command->withholdingAmount, $command->applications, $rates),
                ...$this->check($command->paymentMethod, $command->checkNumber, $command->checkDate, $command->checkStatus),
                'notes' => $command->notes,
                'status' => 'draft',
                'created_by' => $command->createdBy,
            ]);

            $this->syncApplications($collection, $command->applications, $command->createdBy);
        });
    }

    public function findById(string $id, ?string $companyId = null): ?ClientCollection
    {
        return ClientCollection::query()
            ->with($this->detailRelations())
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->find($id);
    }

    public function findOrFail(string $id, ?string $companyId = null): ClientCollection
    {
        return ClientCollection::query()
            ->with($this->detailRelations())
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->findOrFail($id);
    }

    public function update(ClientCollection $model, UpdateClientCollectionCommand $command, DocumentRatesData $rates): void
    {
        DB::transaction(function () use ($model, $command, $rates): void {
            /** El origen se congeló al crear el cobro: no se reescribe aquí. */
            $model->update([
                'client_id' => $command->clientId,
                'credit_source_id' => $this->creditSource($command->paymentMethod, $command->creditSourceId),
                'collection_date' => $command->collectionDate,
                'payment_method' => $command->paymentMethod,
                'reference' => $command->reference,
                'bank_account' => $command->bankAccount,
                'collected_by' => $command->collectedBy,
                'route_id' => $command->routeId,
                ...$rates->toAttributes(),
                ...$this->totals($command->amount, $command->withholdingAmount, $command->applications, $rates),
                ...$this->check($command->paymentMethod, $command->checkNumber, $command->checkDate, $command->checkStatus),
                'notes' => $command->notes,
            ]);

            $this->syncApplications($model, $command->applications, $model->created_by);
        });
    }

    public function updateStatus(ClientCollection $model, UpdateStatusClientCollectionCommand $command): void
    {
        $attributes = ['status' => $command->status];

        if ($command->status === 'cancelled') {
            $attributes['cancelled_at'] = now();
            $attributes['cancellation_reason'] = $command->cancellationReason;
        }

        $model->update($attributes);
    }

    public function updateCheckStatus(
        ClientCollection $model,
        UpdateCheckStatusClientCollectionCommand $command,
    ): void {
        $model->update(['check_status' => $command->checkStatus]);
    }

    /**
     * @return array<int, ClientCollectionApplication>
     */
    public function activeApplications(ClientCollection $model): array
    {
        return ClientCollectionApplication::query()
            ->where('source_type', $model->applicationSource())
            ->where('source_id', $model->applicationSourceId())
            ->where('status', 'active')
            ->orderBy('created_at')
            ->get()
            ->all();
    }

    /**
     * Las aplicaciones vivas de cualquier origen —cobro, anticipo o nota de
     * crédito—, en el orden en que se escribieron.
     *
     * @return array<int, ClientCollectionApplication>
     */
    public function applicationsOf(string $sourceType, string $sourceId): array
    {
        return ClientCollectionApplication::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('status', 'active')
            ->orderBy('created_at')
            ->get()
            ->all();
    }

    public function findApplication(
        string $sourceType,
        string $sourceId,
        string $salesInvoiceId,
    ): ?ClientCollectionApplication {
        return ClientCollectionApplication::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('sales_invoice_id', $salesInvoiceId)
            ->first();
    }

    /**
     * Escribe —o reactiva— la fila con la que un anticipo o una nota abona una
     * factura. La tabla es única por `(factura, origen)`, así que una segunda
     * aplicación del mismo documento a la misma factura reescribe su fila en vez
     * de agregar otra.
     */
    public function writeApplication(
        WriteClientCollectionApplicationCommand $command,
    ): ClientCollectionApplication {
        $attributes = [
            'company_id' => $command->companyId,
            'applied_amount' => $command->appliedAmount,
            'applied_at' => now(),
            'exchange_rate' => $command->exchangeRate,
            'exchange_difference' => $command->exchangeDifference,
            'status' => 'active',
        ];

        $existing = $this->findApplication(
            $command->sourceType,
            $command->sourceId,
            $command->salesInvoiceId,
        );

        if ($existing !== null) {
            $existing->update($attributes);

            return $existing;
        }

        return ClientCollectionApplication::create([
            ...$attributes,
            'sales_invoice_id' => $command->salesInvoiceId,
            'source_type' => $command->sourceType,
            'source_id' => $command->sourceId,
            'created_by' => $command->createdBy,
        ]);
    }

    public function postApplication(
        ClientCollectionApplication $application,
        PostClientCollectionApplicationCommand $command,
    ): void {
        $application->update([
            'applied_at' => $command->appliedAt,
            'exchange_rate' => $command->exchangeRate,
            'exchange_difference' => $command->exchangeDifference,
            'status' => 'active',
        ]);
    }

    public function reverseApplication(ClientCollectionApplication $application): void
    {
        $application->update(['status' => 'reversed']);
    }

    /**
     * @return array{ data: ClientCollection[], total: int }
     */
    public function search(SearchClientCollectionCommand $command): array
    {
        $query = ClientCollection::query()
            ->with(['client'])
            ->when($command->companyId, fn ($q) => $q->where('company_id', $command->companyId));

        $query = $this->apply($query, $command->filters);

        $total = $query->count();

        $data = $query->orderByDesc('collection_date')
            ->orderByDesc('code')
            ->limit($command->limit)
            ->offset($command->offset)
            ->get();

        return ['data' => $data->all(), 'total' => $total];
    }

    /**
     * @return array<int, string>
     */
    private function detailRelations(): array
    {
        return ['client', 'collector', 'route', 'applications.salesInvoice'];
    }

    /**
     * Alinea el reparto con lo enviado desde la pantalla.
     *
     * La fila se reconoce por la factura que abona, no por un id del cliente:
     * la tabla es única por `(factura, origen)`, así que un cobro abona una
     * factura una sola vez y corregir el monto reescribe esa misma fila. Las
     * que dejan de venir no se borran, se revierten.
     *
     * Escribir la fila no mueve todavía el saldo de la factura: eso lo hace el
     * cobro al confirmarse.
     *
     * @param  array<int, ClientCollectionApplicationData>  $applications
     */
    private function syncApplications(ClientCollection $collection, array $applications, ?string $createdBy): void
    {
        $source = $collection->applicationSource();
        $sourceId = $collection->applicationSourceId();

        $existing = ClientCollectionApplication::query()
            ->where('source_type', $source)
            ->where('source_id', $sourceId)
            ->get()
            ->keyBy('sales_invoice_id');

        $keep = [];

        foreach ($this->activeRows($applications) as $row) {
            $attributes = [
                'company_id' => $collection->company_id,
                'applied_amount' => $row->appliedAmount,
                'applied_at' => now(),
                'exchange_rate' => $collection->exchange_rate,
                'status' => 'active',
            ];

            $current = $existing->get($row->salesInvoiceId);

            if ($current !== null) {
                $current->update($attributes);
                $keep[] = $current->id;

                continue;
            }

            $keep[] = ClientCollectionApplication::create([
                ...$attributes,
                'sales_invoice_id' => $row->salesInvoiceId,
                'source_type' => $source,
                'source_id' => $sourceId,
                'exchange_difference' => 0,
                'created_by' => $createdBy,
            ])->id;
        }

        ClientCollectionApplication::query()
            ->where('source_type', $source)
            ->where('source_id', $sourceId)
            ->when($keep !== [], fn ($q) => $q->whereNotIn('id', $keep))
            ->update(['status' => 'reversed']);
    }

    /**
     * De qué crédito sale el cobro. Solo tiene sentido cobrando con un anticipo
     * o con una nota; con dinero de por medio se limpia.
     */
    private function creditSource(string $paymentMethod, ?string $creditSourceId): ?string
    {
        return isset(ClientCollection::CREDIT_METHODS[$paymentMethod]) ? $creditSourceId : null;
    }

    /**
     * Importes de la cabecera.
     *
     * Lo que el cobro puede saldar no es solo lo que entró en caja: la
     * retención que el cliente practicó también cancela deuda, aunque la entere
     * al fisco en vez de pagárnosla. Por eso el excedente sin aplicar se mide
     * contra `amount + withholding_amount`; sin retención —el caso normal— la
     * cuenta es exactamente `amount - applied_amount`.
     *
     * @param  array<int, ClientCollectionApplicationData>  $applications
     * @return array<string, float>
     */
    private function totals(
        float $amount,
        float $withholdingAmount,
        array $applications,
        DocumentRatesData $rates,
    ): array {
        $amount = round($amount, 2);
        $withholding = round($withholdingAmount, 2);
        $applied = $this->appliedTotal($applications);

        return [
            'amount' => $amount,
            'withholding_amount' => $withholding,
            'applied_amount' => $applied,
            'unapplied_amount' => round($amount + $withholding - $applied, 2),
            /** El cobro tiene valor legal: lo que entró en bolívares queda escrito. */
            'amount_ves' => round($amount * $rates->exchangeRate, $rates->amountDecimals),
        ];
    }

    /**
     * Los datos del cheque solo tienen sentido si se cobró con uno; con
     * cualquier otra forma de pago se limpian, y un cheque nuevo nace
     * `pending`: todavía no se ha depositado.
     *
     * @return array<string, string|null>
     */
    private function check(string $paymentMethod, ?string $number, ?string $date, ?string $status): array
    {
        if ($paymentMethod !== 'check') {
            return ['check_number' => null, 'check_date' => null, 'check_status' => null];
        }

        return [
            'check_number' => $number,
            'check_date' => $date,
            'check_status' => $status ?? 'pending',
        ];
    }

    /**
     * @param  array<int, ClientCollectionApplicationData>  $applications
     */
    private function appliedTotal(array $applications): float
    {
        return round(array_sum(array_map(
            static fn (ClientCollectionApplicationData $row): float => $row->appliedAmount,
            $this->activeRows($applications),
        )), 2);
    }

    /**
     * @param  array<int, ClientCollectionApplicationData>  $applications
     * @return array<int, ClientCollectionApplicationData>
     */
    private function activeRows(array $applications): array
    {
        return array_values(array_filter(
            $applications,
            static fn (ClientCollectionApplicationData $row): bool => $row->status === 'active',
        ));
    }

    /**
     * Generate the next sequential per-company code (COB000001, COB000002, …).
     */
    private function generateNextCode(string $companyId): string
    {
        $last = ClientCollection::query()
            ->where('company_id', $companyId)
            ->where('code', 'like', ClientCollection::CODE_PREFIX.'%')
            ->lockForUpdate()
            ->orderByDesc('code')
            ->value('code');

        $next = $last !== null
            ? ((int) substr($last, strlen(ClientCollection::CODE_PREFIX))) + 1
            : 1;

        return ClientCollection::CODE_PREFIX.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
