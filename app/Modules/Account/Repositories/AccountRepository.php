<?php

declare(strict_types=1);

namespace App\Modules\Account\Repositories;

use App\Modules\Account\Commands\CreateAccountCommand;
use App\Modules\Account\Commands\RenewAccountCommand;
use App\Modules\Account\Commands\SearchAccountCommand;
use App\Modules\Account\Commands\UpdateAccountCommand;
use App\Modules\Account\Models\Account;
use App\Modules\Account\Models\AccountRenewal;
use App\Modules\Account\Models\Profile;
use App\Modules\Account\Repositories\Contracts\AccountRepositoryInterface;
use App\Modules\Service\Models\Service;
use App\Modules\Transaction\Commands\CreateTransactionCommand;
use App\Modules\Transaction\Models\Transaction;
use App\Modules\Transaction\Repositories\Contracts\TransactionRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AccountRepository extends AccountFilters implements AccountRepositoryInterface
{
    public function __construct(
        private readonly TransactionRepositoryInterface $transactionRepository,
    ) {}

    public function create(CreateAccountCommand $command): void
    {
        DB::transaction(function () use ($command): void {
            $account = Account::create([
                'id' => $command->id,
                'company_id' => $command->companyId,
                'code' => $this->generateNextCode($command->companyId),
                'service_id' => $command->serviceId,
                'email' => $command->email,
                'password_encrypted' => $command->password,
                'cost' => $command->cost,
                'purchase_date' => $command->fechaCompra,
                'next_renewal' => $command->proximaRenovacion,
                'status' => $command->status,
                'notes' => $command->notes,
            ]);

            $this->generateProfiles($account, $command);

            $this->recordPurchase($account);

            $this->recordPurchaseTransaction($account, $command->createdBy);
        });
    }

    public function findById(string $id, ?string $companyId = null): ?Account
    {
        return Account::query()
            ->with($this->showRelations())
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->find($id);
    }

    public function findOrFail(string $id, ?string $companyId = null): Account
    {
        return Account::query()
            ->with($this->showRelations())
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->findOrFail($id);
    }

    /**
     * Relaciones cargadas para la pantalla de detalle de la account.
     *
     * @return array<int|string, mixed>
     */
    private function showRelations(): array
    {
        return [
            'service',
            'profiles' => fn ($q) => $q->orderBy('number'),
            'renewals' => fn ($q) => $q->orderByDesc('paid_at')->orderByDesc('created_at'),
        ];
    }

    public function update(Account $model, UpdateAccountCommand $command): void
    {
        DB::transaction(function () use ($model, $command): void {
            $attributes = [
                'email' => $command->email,
                'cost' => $command->cost,
                'purchase_date' => $command->fechaCompra,
                'next_renewal' => $command->proximaRenovacion,
                'status' => $command->status,
                'notes' => $command->notes,
            ];

            if ($command->password !== null) {
                $attributes['password_encrypted'] = $command->password;
            }

            $model->update($attributes);

            $this->updateProfiles($model, $command);
        });
    }

    public function renew(Account $model, RenewAccountCommand $command): void
    {
        DB::transaction(function () use ($model, $command): void {
            $previousRenewal = $model->next_renewal->toDateString();

            AccountRenewal::create([
                'id' => $command->id,
                'company_id' => $model->company_id,
                'account_id' => $model->id,
                'type' => AccountRenewal::TYPE_RENEWAL,
                'amount' => $command->amount,
                'period_start' => $previousRenewal,
                'period_end' => $command->nextRenewal,
                'paid_at' => now()->toDateString(),
                'notes' => $command->notes,
                'created_by' => $command->createdBy,
            ]);

            $model->update([
                'next_renewal' => $command->nextRenewal,
                'cost' => $command->amount,
            ]);

            $this->recordRenewalTransaction($model, $command, $previousRenewal);
        });
    }

    /**
     * Registra el movimiento inicial de compra al crear la account: el primer
     * período va desde la fecha de compra hasta la primera fecha de vencimiento.
     */
    private function recordPurchase(Account $account): void
    {
        AccountRenewal::create([
            'id' => Str::uuid7()->toString(),
            'company_id' => $account->company_id,
            'account_id' => $account->id,
            'type' => AccountRenewal::TYPE_PURCHASE,
            'amount' => $account->cost,
            'period_start' => $account->purchase_date->toDateString(),
            'period_end' => $account->next_renewal->toDateString(),
            'paid_at' => $account->purchase_date->toDateString(),
            'notes' => null,
            'created_by' => null,
        ]);
    }

    /**
     * Registra el gasto inicial de compra de la cuenta en el libro de
     * transacciones (fuente de verdad financiera).
     */
    private function recordPurchaseTransaction(Account $account, ?string $createdBy): void
    {
        $this->transactionRepository->create(new CreateTransactionCommand(
            id: Str::uuid7()->toString(),
            companyId: $account->company_id,
            type: Transaction::TYPE_EXPENSE,
            category: 'streaming_account',
            amount: (float) $account->cost,
            date: $account->purchase_date->toDateString(),
            paymentMethod: 'cash',
            description: "Compra de cuenta {$account->code}",
            recordedBy: $createdBy,
            relatedType: 'Account',
            relatedId: $account->id,
            periodFrom: $account->purchase_date->toDateString(),
            periodTo: $account->next_renewal->toDateString(),
        ));
    }

    /**
     * Registra el gasto de renovación de la cuenta en el libro de transacciones.
     */
    private function recordRenewalTransaction(Account $account, RenewAccountCommand $command, string $previousRenewal): void
    {
        $this->transactionRepository->create(new CreateTransactionCommand(
            id: Str::uuid7()->toString(),
            companyId: $account->company_id,
            type: Transaction::TYPE_EXPENSE,
            category: 'streaming_account_renewal',
            amount: $command->amount,
            date: now()->toDateString(),
            paymentMethod: 'cash',
            description: "Renovación de cuenta {$account->code}",
            recordedBy: $command->createdBy,
            relatedType: 'Account',
            relatedId: $account->id,
            periodFrom: $previousRenewal,
            periodTo: $command->nextRenewal,
        ));
    }

    /**
     * @return array{ data: Account[], total: int }
     */
    public function search(SearchAccountCommand $command): array
    {
        $query = Account::query()
            ->with(['service', 'profiles'])
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
     * Auto-genera N profiles (1..N) donde N = service.max_profiles, en status
     * 'available', aplicando los PINs precargados que vengan en el comando.
     */
    private function generateProfiles(Account $account, CreateAccountCommand $command): void
    {
        $maxProfiles = (int) Service::query()
            ->where('id', $command->serviceId)
            ->value('max_profiles');

        $pinsByNumber = [];
        foreach ($command->profiles as $profile) {
            $pinsByNumber[$profile['number']] = $profile['pin'];
        }

        for ($number = 1; $number <= $maxProfiles; $number++) {
            Profile::create([
                'account_id' => $account->id,
                'number' => $number,
                'pin' => $pinsByNumber[$number] ?? null,
                'status' => 'available',
            ]);
        }
    }

    /**
     * Actualiza PIN, status y notes de los profiles indicados en el comando.
     * Cada profile se localiza por su número dentro de la account.
     */
    private function updateProfiles(Account $account, UpdateAccountCommand $command): void
    {
        if ($command->profiles === []) {
            return;
        }

        $profilesByNumber = $account->profiles()->get()->keyBy('number');

        foreach ($command->profiles as $linea) {
            $profile = $profilesByNumber->get($linea['number']);

            if ($profile === null) {
                continue;
            }

            $attributes = [];

            if (array_key_exists('pin', $linea)) {
                $attributes['pin'] = $linea['pin'];
            }

            if (array_key_exists('notes', $linea)) {
                $attributes['notes'] = $linea['notes'];
            }

            if (($linea['status'] ?? null) !== null) {
                $attributes['status'] = $linea['status'];
            }

            if ($attributes !== []) {
                $profile->update($attributes);
            }
        }
    }

    /**
     * Genera el siguiente código secuencial por compañía (ACC000001, ACC000002, …).
     */
    private function generateNextCode(string $companyId): string
    {
        $last = Account::query()
            ->where('company_id', $companyId)
            ->where('code', 'like', Account::CODE_PREFIX.'%')
            ->lockForUpdate()
            ->orderByDesc('code')
            ->value('code');

        $next = $last !== null
            ? ((int) substr($last, strlen(Account::CODE_PREFIX))) + 1
            : 1;

        return Account::CODE_PREFIX.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
