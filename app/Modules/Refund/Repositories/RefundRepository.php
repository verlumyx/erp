<?php

declare(strict_types=1);

namespace App\Modules\Refund\Repositories;

use App\Modules\Refund\Commands\CreateRefundCommand;
use App\Modules\Refund\Commands\ResolveRefundCommand;
use App\Modules\Refund\Commands\SearchRefundCommand;
use App\Modules\Refund\Commands\UpdateRefundCommand;
use App\Modules\Refund\Models\Refund;
use App\Modules\Refund\Repositories\Contracts\RefundRepositoryInterface;
use App\Modules\Sale\Commands\CancelSaleCommand;
use App\Modules\Sale\Models\Sale;
use App\Modules\Sale\Repositories\Contracts\SaleRepositoryInterface;
use App\Modules\Transaction\Commands\CreateTransactionCommand;
use App\Modules\Transaction\Models\Transaction;
use App\Modules\Transaction\Repositories\Contracts\TransactionRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RefundRepository extends RefundFilters implements RefundRepositoryInterface
{
    public function __construct(
        private readonly SaleRepositoryInterface $saleRepository,
        private readonly TransactionRepositoryInterface $transactionRepository,
    ) {}

    public function create(CreateRefundCommand $command): void
    {
        DB::transaction(function () use ($command): void {
            /** @var Sale $sale */
            $sale = Sale::query()
                ->where('id', $command->saleId)
                ->where('company_id', $command->companyId)
                ->firstOrFail();

            Refund::create([
                'id' => $command->id,
                'company_id' => $command->companyId,
                'code' => $this->generateNextCode($command->companyId),
                'sale_id' => $sale->id,
                'client_id' => $sale->client_id,
                'amount' => $command->amount,
                'reason' => $command->reason,
                'status' => Refund::STATUS_PENDING,
                'requested_by' => $command->requestedBy,
            ]);
        });
    }

    public function approve(Refund $model, ResolveRefundCommand $command): void
    {
        DB::transaction(function () use ($model, $command): void {
            $sale = $this->saleRepository->findOrFail($model->sale_id, $command->companyId);

            // Idempotente: si la venta sigue activa (origen "creación manual") se cancela
            // y se liberan sus profiles. Si ya estaba cancelada (origen "desde cancelación"),
            // solo se registra el egreso.
            if ($sale->status !== Sale::STATUS_CANCELLED) {
                $this->saleRepository->cancel($sale, new CancelSaleCommand(
                    saleId: $sale->id,
                    companyId: $command->companyId,
                    cancellationReason: "Reembolso {$model->code}",
                ));
            }

            $this->recordRefundTransaction($model, $sale, $command->resolvedBy);

            $model->update([
                'status' => Refund::STATUS_APPROVED,
                'resolved_by' => $command->resolvedBy,
                'resolved_at' => now(),
            ]);
        });
    }

    public function reject(Refund $model, ResolveRefundCommand $command): void
    {
        $model->update([
            'status' => Refund::STATUS_REJECTED,
            'resolved_by' => $command->resolvedBy,
            'resolved_at' => now(),
        ]);
    }

    public function update(Refund $model, UpdateRefundCommand $command): void
    {
        $model->update([
            'amount' => $command->amount,
            'reason' => $command->reason,
        ]);
    }

    public function findById(string $id, ?string $companyId = null): ?Refund
    {
        return Refund::query()
            ->with($this->showRelations())
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->find($id);
    }

    public function findOrFail(string $id, ?string $companyId = null): Refund
    {
        return Refund::query()
            ->with($this->showRelations())
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->findOrFail($id);
    }

    /**
     * @return array{ data: Refund[], total: int }
     */
    public function search(SearchRefundCommand $command): array
    {
        $query = Refund::query()
            ->with(['sale', 'client'])
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
     * Registra el egreso contable del reembolso (dinero devuelto al cliente).
     */
    private function recordRefundTransaction(Refund $refund, Sale $sale, ?string $recordedBy): void
    {
        $clientName = $sale->client()->value('name') ?? 'cliente';

        $this->transactionRepository->create(new CreateTransactionCommand(
            id: Str::uuid7()->toString(),
            companyId: $refund->company_id,
            type: Transaction::TYPE_EXPENSE,
            category: 'refund',
            amount: (float) $refund->amount,
            date: now()->toDateString(),
            paymentMethod: 'cash',
            description: "Reembolso venta {$sale->code} a {$clientName}",
            recordedBy: $recordedBy,
            relatedType: 'Refund',
            relatedId: $refund->id,
        ));
    }

    /**
     * Relaciones cargadas para la pantalla de detalle del reembolso.
     *
     * @return array<int|string, mixed>
     */
    private function showRelations(): array
    {
        return [
            'sale',
            'client',
            'requestedBy',
            'resolvedBy',
            'transactions' => fn ($q) => $q->orderByDesc('date')->orderByDesc('created_at'),
        ];
    }

    /**
     * Genera el siguiente código secuencial por compañía (REF000001, REF000002, …).
     */
    private function generateNextCode(string $companyId): string
    {
        $last = Refund::query()
            ->where('company_id', $companyId)
            ->where('code', 'like', Refund::CODE_PREFIX.'%')
            ->lockForUpdate()
            ->orderByDesc('code')
            ->value('code');

        $next = $last !== null
            ? ((int) substr($last, strlen(Refund::CODE_PREFIX))) + 1
            : 1;

        return Refund::CODE_PREFIX.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
