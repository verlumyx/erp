<?php

declare(strict_types=1);

namespace App\Modules\Pos\Repositories;

use App\Modules\Pos\Commands\HoldPosSaleCommand;
use App\Modules\Pos\Commands\PosCheckoutCommand;
use App\Modules\Pos\Commands\SearchPosSaleCommand;
use App\Modules\Pos\Exceptions\PosSaleNotFoundException;
use App\Modules\Pos\Models\PosPayment;
use App\Modules\Pos\Models\PosSale;
use App\Modules\Pos\Models\PosSaleLine;
use App\Modules\Pos\Models\PosSession;
use App\Modules\Pos\Repositories\Contracts\PosSaleRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PosSaleRepository implements PosSaleRepositoryInterface
{
    private const RELATIONS = [
        'client',
        'session.user',
        'register.warehouse',
        'salesInvoice',
        'dispatch',
        'lines.item',
        'lines.measurementUnit',
        'lines.tax',
        'payments',
        'canceller',
        'creator',
    ];

    public function create(
        PosCheckoutCommand $command,
        ?string $invoiceId,
        ?string $dispatchId,
        float $exchangeRate
    ): PosSale {
        return DB::transaction(function () use ($command, $invoiceId, $dispatchId, $exchangeRate): PosSale {
            $code = $this->generateNextCode($command->companyId);
            $posRegisterId = PosSession::where('id', $command->posSessionId)->value('pos_register_id');

            $sale = PosSale::create([
                'id' => $command->id,
                'company_id' => $command->companyId,
                'code' => $code,
                'pos_session_id' => $command->posSessionId,
                'pos_register_id' => $posRegisterId,
                'client_id' => $command->clientId,
                'sales_invoice_id' => $invoiceId,
                'dispatch_id' => $dispatchId,
                'sale_date' => $command->saleDate,
                'currency' => $command->currency,
                'exchange_rate' => $exchangeRate,
                'subtotal' => $command->subtotal,
                'discount_amount' => $command->discountAmount,
                'tax_amount' => $command->taxAmount,
                'igtf_amount' => $command->igtfAmount,
                'total' => $command->total,
                'total_in_local_currency' => round($command->total * $exchangeRate, 2),
                'paid_amount' => $command->paidAmount,
                'change_amount_usd' => $command->changeAmountUsd,
                'change_amount_ves' => $command->changeAmountVes,
                'notes' => $command->notes,
                'status' => PosSale::STATUS_COMPLETED,
                'created_by' => $command->userId,
            ]);

            // Create lines
            foreach ($command->lines as $index => $line) {
                PosSaleLine::create([
                    'id' => (string) Str::uuid(),
                    'company_id' => $command->companyId,
                    'pos_sale_id' => $sale->id,
                    'line_number' => $index + 1,
                    'item_id' => $line->itemId,
                    'measurement_unit_id' => $line->measurementUnitId,
                    'quantity' => $line->quantity,
                    'base_quantity' => $line->quantity,
                    'unit_price' => $line->unitPrice,
                    'discount_percent' => $line->discountPercent,
                    'discount_amount' => $line->discountAmount,
                    'tax_id' => $line->taxId,
                    'tax_percent' => $line->taxPercent,
                    'tax_amount' => $line->taxAmount,
                    'subtotal' => $line->subtotal,
                    'total' => $line->total,
                    'status' => 'active',
                ]);
            }

            // Create payments
            foreach ($command->payments as $payment) {
                PosPayment::create([
                    'id' => (string) Str::uuid(),
                    'company_id' => $command->companyId,
                    'pos_sale_id' => $sale->id,
                    'pos_session_id' => $command->posSessionId,
                    'payment_method' => $payment->paymentMethod,
                    'currency' => $payment->currency,
                    'exchange_rate' => $payment->exchangeRate,
                    'amount_tendered' => $payment->amountTendered,
                    'amount_applied' => $payment->amountApplied,
                    'amount_in_document_currency' => $payment->amountInDocumentCurrency,
                    'reference' => $payment->reference,
                    'card_last_digits' => $payment->cardLastDigits,
                    'status' => 'active',
                ]);
            }

            return $this->findOrFail($sale->id, $command->companyId);
        });
    }

    public function createDraft(HoldPosSaleCommand $command, float $exchangeRate): PosSale
    {
        return DB::transaction(function () use ($command, $exchangeRate): PosSale {
            $code = $this->generateNextCode($command->companyId);
            $posRegisterId = PosSession::where('id', $command->posSessionId)->value('pos_register_id');

            $sale = PosSale::create([
                'id' => $command->id,
                'company_id' => $command->companyId,
                'code' => $code,
                'pos_session_id' => $command->posSessionId,
                'pos_register_id' => $posRegisterId,
                'client_id' => $command->clientId,
                'sale_date' => $command->saleDate,
                'currency' => $command->currency,
                'exchange_rate' => $exchangeRate,
                'subtotal' => $command->subtotal,
                'discount_amount' => $command->discountAmount,
                'tax_amount' => $command->taxAmount,
                'total' => $command->total,
                'total_in_local_currency' => round($command->total * $exchangeRate, 2),
                'notes' => $command->notes,
                'status' => PosSale::STATUS_DRAFT,
                'created_by' => $command->userId,
            ]);

            foreach ($command->lines as $index => $line) {
                PosSaleLine::create([
                    'id' => (string) Str::uuid(),
                    'company_id' => $command->companyId,
                    'pos_sale_id' => $sale->id,
                    'line_number' => $index + 1,
                    'item_id' => $line->itemId,
                    'measurement_unit_id' => $line->measurementUnitId,
                    'quantity' => $line->quantity,
                    'base_quantity' => $line->quantity,
                    'unit_price' => $line->unitPrice,
                    'discount_percent' => $line->discountPercent,
                    'discount_amount' => $line->discountAmount,
                    'tax_id' => $line->taxId,
                    'tax_percent' => $line->taxPercent,
                    'tax_amount' => $line->taxAmount,
                    'subtotal' => $line->subtotal,
                    'total' => $line->total,
                    'status' => 'active',
                ]);
            }

            return $this->findOrFail($sale->id, $command->companyId);
        });
    }

    public function cancel(PosSale $sale, string $cancelledBy, ?string $reason = null): PosSale
    {
        $sale->update([
            'status' => PosSale::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancelled_by' => $cancelledBy,
            'cancellation_reason' => $reason,
        ]);

        return $sale->refresh();
    }

    public function deleteDraft(PosSale $sale): void
    {
        if ($sale->status === PosSale::STATUS_DRAFT) {
            $sale->delete();
        }
    }

    /**
     * @return array<int, PosSale>
     */
    public function getHeldSales(string $sessionId): array
    {
        return PosSale::query()
            ->with(self::RELATIONS)
            ->where('pos_session_id', $sessionId)
            ->where('status', PosSale::STATUS_DRAFT)
            ->orderByDesc('created_at')
            ->get()
            ->all();
    }

    public function findById(string $id, ?string $companyId = null): ?PosSale
    {
        return PosSale::query()
            ->with(self::RELATIONS)
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->find($id);
    }

    public function findOrFail(string $id, ?string $companyId = null): PosSale
    {
        $sale = $this->findById($id, $companyId);

        if (! $sale) {
            throw new PosSaleNotFoundException;
        }

        return $sale;
    }

    /**
     * @return LengthAwarePaginator<int, PosSale>
     */
    public function paginate(SearchPosSaleCommand $command): LengthAwarePaginator
    {
        $query = PosSale::query()
            ->with(self::RELATIONS)
            ->where('company_id', $command->companyId);

        if ($command->search) {
            $term = '%'.trim($command->search).'%';
            $query->where(function ($q) use ($term) {
                $q->where('code', 'ilike', $term)
                    ->orWhereHas('client', fn ($cq) => $cq->where('name', 'ilike', $term));
            });
        }

        if (! empty($command->filters['status'])) {
            $query->where('status', $command->filters['status']);
        }

        if (! empty($command->filters['pos_session_id'])) {
            $query->where('pos_session_id', $command->filters['pos_session_id']);
        }

        if (! empty($command->filters['pos_register_id'])) {
            $query->where('pos_register_id', $command->filters['pos_register_id']);
        }

        if (! empty($command->filters['client_id'])) {
            $query->where('client_id', $command->filters['client_id']);
        }

        if (! empty($command->filters['from_date'])) {
            $query->where('sale_date', '>=', $command->filters['from_date']);
        }

        if (! empty($command->filters['to_date'])) {
            $query->where('sale_date', '<=', $command->filters['to_date']);
        }

        $sortField = in_array($command->sortBy, ['sale_date', 'code', 'total', 'created_at'], true)
            ? $command->sortBy
            : 'sale_date';

        $direction = strtolower($command->sortDirection) === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sortField, $direction)
            ->paginate($command->perPage, ['*'], 'page', $command->page);
    }

    private function generateNextCode(string $companyId): string
    {
        $last = PosSale::query()
            ->where('company_id', $companyId)
            ->where('code', 'like', PosSale::CODE_PREFIX.'%')
            ->lockForUpdate()
            ->orderByDesc('code')
            ->value('code');

        $next = $last !== null
            ? ((int) substr($last, strlen(PosSale::CODE_PREFIX))) + 1
            : 1;

        return PosSale::CODE_PREFIX.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
